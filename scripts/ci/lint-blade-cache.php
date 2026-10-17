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
        preg_match_all('/<\?php\s+if\s*\([\s\S]*?\?>|<\?php\s+endif\s*;\s*\?>/s', $contents, $tokens, PREG_OFFSET_CAPTURE);
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
    if ($contents !== false) {
        $raw = [
            'if' => preg_match_all('/<\?php\s+if\s*\(/', $contents),
            'endif' => preg_match_all('/<\?php\s+endif\s*;/', $contents),
            'foreach' => preg_match_all('/<\?php\s+foreach\s*\(/', $contents),
            'endforeach' => preg_match_all('/<\?php\s+endforeach\s*;/', $contents),
            'for' => preg_match_all('/<\?php\s+for\s*\(/', $contents),
            'endfor' => preg_match_all('/<\?php\s+endfor\s*;/', $contents),
        ];
        fwrite(STDERR, "Compiled directive counts: " . json_encode($raw) . "\n");

        $controlTypes = [
            T_IF => 'if', T_ENDIF => 'endif',
            T_FOREACH => 'foreach', T_ENDFOREACH => 'endforeach',
            T_FOR => 'for', T_ENDFOR => 'endfor',
            T_WHILE => 'while', T_ENDWHILE => 'endwhile',
            T_SWITCH => 'switch', T_ENDSWITCH => 'endswitch',
        ];
        $controlCounts = [];
        $starts = [];
        $ends = [];
        foreach (token_get_all($contents) as $token) {
            if (!is_array($token) || !isset($controlTypes[$token[0]])) {
                continue;
            }
            $name = $controlTypes[$token[0]];
            $controlCounts[$name] = ($controlCounts[$name] ?? 0) + 1;
            if (str_starts_with($name, 'end')) {
                $ends[] = [$name, $token[2]];
            } else {
                $starts[] = [$name, $token[2]];
            }
        }
        fwrite(STDERR, "PHP token control counts: " . json_encode($controlCounts) . "\n");
        fwrite(STDERR, "Last PHP control openings: " . json_encode(array_slice($starts, -20)) . "\n");

        // Parse PHP alternative control syntax (if(...): ... endif;) with
        // nesting, not just counts. Laravel's generated loops also contain
        // foreach(...): and endforeach; in long mixed HTML/PHP templates.
        $phpTokens = token_get_all($contents);
        $openingTokens = [
            T_IF => 'if', T_FOREACH => 'foreach', T_FOR => 'for',
            T_WHILE => 'while', T_SWITCH => 'switch',
        ];
        $closingTokens = [
            T_ENDIF => 'if', T_ENDFOREACH => 'foreach', T_ENDFOR => 'for',
            T_ENDWHILE => 'while', T_ENDSWITCH => 'switch',
        ];
        $altStack = [];
        $altMismatches = [];
        $tokenCount = count($phpTokens);
        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $phpTokens[$i];
            if (!is_array($token)) {
                continue;
            }
            $id = $token[0];
            if (isset($openingTokens[$id])) {
                $start = $i + 1;
                while ($start < $tokenCount
                    && is_array($phpTokens[$start])
                    && $phpTokens[$start][0] === T_WHITESPACE) {
                    $start++;
                }
                if (($phpTokens[$start] ?? null) !== '(') {
                    continue;
                }
                $depth = 0;
                $after = null;
                for ($j = $start; $j < $tokenCount; $j++) {
                    if ($phpTokens[$j] === '(') {
                        $depth++;
                    } elseif ($phpTokens[$j] === ')') {
                        $depth--;
                        if ($depth === 0) {
                            $after = $j + 1;
                            break;
                        }
                    }
                }
                if ($after === null) {
                    continue;
                }
                while ($after < $tokenCount
                    && is_array($phpTokens[$after])
                    && in_array($phpTokens[$after][0], [T_WHITESPACE, T_COMMENT], true)) {
                    $after++;
                }
                if (($phpTokens[$after] ?? null) === ':') {
                    $altStack[] = ['type' => $openingTokens[$id], 'line' => $token[2]];
                }
            } elseif (isset($closingTokens[$id])) {
                $expected = $closingTokens[$id];
                $top = array_pop($altStack);
                if ($top === null || $top['type'] !== $expected) {
                    $altMismatches[] = [
                        'close' => $expected,
                        'line' => $token[2],
                        'top' => $top,
                    ];
                }
            }
        }
        fwrite(STDERR, "Alternative PHP syntax unclosed: " . json_encode(array_slice($altStack, -18)) . "\n");
        fwrite(STDERR, "Alternative PHP syntax mismatches: " . json_encode(array_slice($altMismatches, -18)) . "\n");
        if ($altStack) {
            $from = max(1, $altStack[0]['line'] - 3);
            $compiledLines = explode("\n", $contents);
            fwrite(STDERR, "PHP compiled source around unclosed directive:\n");
            for ($idx = $from; $idx < min(count($compiledLines), $from + 82); $idx++) {
                fwrite(STDERR, sprintf("%5d | %s\n", $idx, $compiledLines[$idx - 1]));
            }
        }


        $bracketStack = [];
        $unexpected = [];
        $currentLine = 1;
        $pairs = [')' => '(', '}' => '{', ']' => '['];
        foreach (token_get_all($contents) as $token) {
            if (is_array($token)) {
                $currentLine = $token[2] + substr_count($token[1], "\n");
                continue;
            }
            if (in_array($token, ['(', '{', '['], true)) {
                $bracketStack[] = ['symbol' => $token, 'line' => $currentLine];
            } elseif (isset($pairs[$token])) {
                $opened = array_pop($bracketStack);
                if ($opened === null || $opened['symbol'] !== $pairs[$token]) {
                    $unexpected[] = ['symbol' => $token, 'line' => $currentLine, 'opened' => $opened];
                }
            }
            $currentLine += substr_count($token, "\n");
        }
        if ($bracketStack) {
            fwrite(STDERR, "Unclosed PHP token brackets: " . json_encode(array_slice($bracketStack, -20)) . "\n");
        }
        if ($unexpected) {
            fwrite(STDERR, "Unexpected PHP token brackets: " . json_encode(array_slice($unexpected, -10)) . "\n");
        }
    }

    if (preg_match('/on line (\d+)/', $text, $match) && $contents !== false) {
        $line = (int) $match[1];
        $lines = explode("\n", $contents);
        for ($n = max(1, $line - 12); $n <= min(count($lines), $line + 7); $n++) {
            fwrite(STDERR, sprintf("%5d | %s\n", $n, $lines[$n - 1]));
        }
    }

    // Continue to expose every invalid compiled view in one CI run.
    // The developer fixes sources; this script never rewrites templates.
    continue;
}

printf("Blade compiled PHP lint: %d files checked, %d failures.\n", $count, $failed);
exit($failed > 0 ? 1 : 0);
