<?php
/**
 * CI-only: lint the compiled PHP of *every* Blade template in the checkout.
 *
 * "php artisan view:cache" can succeed even when the resulting PHP fails to
 * parse. This command catches it before a beta is deployed.
 *
 * Never edits application code or customer files.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$folder = $root . '/storage/framework/views';
$paths = glob($folder . '/*.php');
if ($paths === false || count($paths) < 100) {
    fwrite(STDERR, "Compiled Blade cache is empty/incomplete. Run php artisan view:cache first.\n");
    exit(2);
}

$pathPrefix = $root . '/';
$count = 0;
$failed = 0;
foreach ($paths as $file) {
    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);
    $count++;
    if ($status === 0) {
        continue;
    }

    $failed++;
    $text = implode("\n", $output);
    fwrite(STDERR, "Compiled PHP has a syntax error:\n" . $text . "\n");
    $contents = file_get_contents($file);
    $source = 'unknown Blade source';
    if ($contents !== false && preg_match('~/\*\*PATH\s+(.+?)\s+ENDPATH\*\*/~s', $contents, $match)) {
        $source = str_replace($pathPrefix, '', trim($match[1]));
    }
    fwrite(STDERR, "SOURCE BLADE: {$source}\n");
    // Diagnose unclosed Blade "if:" structures even when the parser only
    // reports a generic unexpected EOF on the last compiled line.
    if ($contents !== false) {
        preg_match_all('/<\?php\s+if\s*\([^?]*\?>|<\?php\s+endif\s*;\s*\?>/s', $contents, $tokens, PREG_OFFSET_CAPTURE);
        $open = [];
        $orphan = [];
        foreach ($tokens[0] ?? [] as [$token, $offset]) {
            $lineNumber = substr_count($contents, "\n", 0, $offset) + 1;
            if (preg_match('/<\?php\s+endif\b/', $token)) {
                if (count($open) === 0) {
                    $orphan[] = $lineNumber;
                } else {
                    array_pop($open);
                }
            } elseif (preg_match('/:\s*\?>$/', $token)) {
                $open[] = [
                    'line' => $lineNumber,
                    'text' => preg_replace('/\s+/', ' ', substr($token, 0, 230)),
                ];
            }
        }
        if (count($open)) {
            fwrite(STDERR, "UNMATCHED COMPILED if/endif, most recent opens:\n");
            foreach (array_slice($open, -12) as $entry) {
                fwrite(STDERR, "  Line {$entry['line']}: {$entry['text']}\n");
            }
        }
        if (count($orphan)) {
            fwrite(STDERR, "Orphan endif lines: " . implode(', ', $orphan) . "\n");
        }
    }

    fwrite(STDERR, "COMPILED FILE: {$file}\n");
    if (preg_match('/on line (\d+)/', $text, $match) && $contents !== false) {
        $line = (int) $match[1];
        $lines = explode("\n", $contents);
        for ($n = max(1, $line - 12); $n <= min(count($lines), $line + 7); $n++) {
            fwrite(STDERR, sprintf("%5d | %s\n", $n, $lines[$n - 1]));
        }
    }

    // Bail immediately to avoid hiding the first cause.
    break;
}

printf("Blade compiled PHP lint: %d files checked, %d failures.\n", $count, $failed);
exit($failed > 0 ? 1 : 0);
