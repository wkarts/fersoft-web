<?php

/**
 * Auditoria somente leitura dos assets da aplicação.
 * php scripts/auditar-assets.php
 * php scripts/auditar-assets.php --out=storage/app/tema-assets.json
 */
$root = dirname(__DIR__);
$areas = ['public', 'resources/js', 'resources/css'];
$extensions = ['js', 'mjs', 'css', 'scss', 'ts', 'tsx'];
$patterns = [
    'jquery' => '/(?:jQuery\s*\(|\$\s*\()/',
    'kt_runtime' => '/\bKT(?:App|Util|Menu|Wizard|Datatable|Select2|Layout|Header|Aside|Scroll|Card|ImageInput|BootstrapDatepicker|.*?)\b/',
    'bootstrap4_attrs' => '/data-(?:toggle|dismiss|target)\s*=/i',
    'bootstrap5_attrs' => '/data-bs-(?:toggle|dismiss|target)\s*=/i',
    'modal' => '/(?:\.modal\s*\(|\bmodal\b)/i',
    'datatable' => '/\b(?:DataTable|datatable|KTDatatable)\b/i',
    'select2' => '/select2/i',
    'ajax' => '/(?:\$\.(?:ajax|get|post)\s*\(|\bfetch\s*\()/',
    'datepicker' => '/datepicker|daterangepicker/i',
    'mask' => '/\bmask(?:ed)?\s*\(|(?:jquery\.mask)/i',
    'toast' => '/toastr|sweetalert|swal\s*\(/i',
    'fullcalendar' => '/fullcalendar/i',
    'important' => '/!important/i',
    'import' => '/(?:\@import\b|\bimport\s+(?:.*?\bfrom\s+)?["\x27])/i',
];
$files = [];
$markers = array_fill_keys(array_keys($patterns), 0);
$groups = [];
$hashes = [];
$failures = [];

foreach ($areas as $area) {
    $dir = $root . '/' . $area;
    if (!is_dir($dir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        if (!in_array($ext, $extensions, true)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $source = @file_get_contents($file->getPathname());
        if ($source === false) {
            $failures[] = $relative;
            continue;
        }
        $size = strlen($source);
        $category = strpos($relative, 'public/metronic/') === 0 ? 'metronic'
            : (strpos($relative, 'public/js/') === 0 ? 'public-js'
            : (strpos($relative, 'public/css/') === 0 ? 'public-css'
            : (strpos($relative, 'resources/') === 0 ? 'resources' : 'demais-publicos')));
        if (!isset($groups[$category])) {
            $groups[$category] = ['files' => 0, 'bytes' => 0, 'javascript' => 0, 'styles' => 0];
        }
        $groups[$category]['files']++;
        $groups[$category]['bytes'] += $size;
        if (in_array($ext, ['css', 'scss'], true)) {
            $groups[$category]['styles']++;
        } else {
            $groups[$category]['javascript']++;
        }
        $counts = [];
        foreach ($patterns as $label => $pattern) {
            $matches = preg_match_all($pattern, $source);
            $count = $matches === false ? 0 : $matches;
            $counts[$label] = $count;
            $markers[$label] += $count;
        }
        $sha = hash('sha256', $source);
        $hashes[$sha][] = $relative;
        $files[] = [
            'path' => $relative,
            'extension' => $ext,
            'category' => $category,
            'bytes' => $size,
            'sha256' => $sha,
            'markers' => array_filter($counts, static function ($n) { return $n > 0; }),
        ];
    }
}
ksort($groups);
usort($files, static function ($a, $b) { return strcmp($a['path'], $b['path']); });
$duplicates = [];
foreach ($hashes as $sha => $paths) {
    if (count($paths) > 1) {
        $duplicates[] = ['sha256' => $sha, 'paths' => $paths];
    }
}
$report = [
    'generated_at' => gmdate('c'),
    'summary' => [
        'scanned_assets' => count($files),
        'total_bytes' => array_sum(array_column($files, 'bytes')),
        'groups' => $groups,
        'marker_occurrences' => $markers,
        'duplicate_hash_groups' => count($duplicates),
        'unreadable' => $failures,
    ],
    'files' => $files,
    'duplicates' => $duplicates,
];
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    fwrite(STDERR, "Não foi possível serializar auditoria de assets.\n");
    exit(1);
}
$out = null;
$asJson = false;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--json') {
        $asJson = true;
    } elseif (strpos($argument, '--out=') === 0) {
        $out = substr($argument, 6);
    }
}
if ($out !== null) {
    if ($out === '') {
        fwrite(STDERR, "Destino vazio.\n");
        exit(1);
    }
    $target = $out[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\/]/', $out) ? $out : $root . '/' . $out;
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0775, true) && !is_dir(dirname($target))) {
        fwrite(STDERR, "Falha criando diretório de saída.\n");
        exit(1);
    }
    if (file_put_contents($target, $json . PHP_EOL, LOCK_EX) === false) {
        fwrite(STDERR, "Falha ao escrever auditoria de assets.\n");
        exit(1);
    }
}
if ($asJson) {
    echo $json, PHP_EOL;
    exit(0);
}
printf("Assets auditados: %d | Volume: %.2f MiB | Duplicações SHA: %d | Ilegíveis: %d\n",
    count($files),
    $report['summary']['total_bytes'] / 1048576,
    count($duplicates),
    count($failures)
);
foreach ($groups as $name => $s) {
    printf("  %-21s %4d arquivos | %7.2f MiB | JS/TS %3d | CSS/SCSS %3d\n",
        $name, $s['files'], $s['bytes'] / 1048576, $s['javascript'], $s['styles']
    );
}
foreach ($markers as $name => $num) {
    printf("  [marker] %-19s %d\n", $name, $num);
}
if (count($failures) !== 0) {
    exit(2);
}
