<?php

/**
 * Inventário não destrutivo das superfícies visuais Blade do FERSOFT WEB.
 *
 * php scripts/auditar-tema.php
 * php scripts/auditar-tema.php --json
 * php scripts/auditar-tema.php --out=storage/app/tema-inventario.json
 *
 * Nenhuma dependência extra; não utiliza banco de dados nem modifica views.
 */
$root = dirname(__DIR__);
$viewRoot = $root . '/resources/views';

if (!is_dir($viewRoot)) {
    fwrite(STDERR, "Diretório de views indisponível.\n");
    exit(1);
}

$features = [
    'modais' => '/class\s*=\s*["\x27][^"\x27>]*\bmodal\s+fade\b/i',
    'acoes_modal' => '/data-(?:bs-)?toggle\s*=\s*["\x27]modal["\x27]/i',
    'tabelas' => '/<table\b/i',
    'datatables' => '/\b(?:datatable|DataTable)\b/i',
    'select2' => '/\bselect2\b/i',
    'formularios' => '/<form\b/i',
    'wizards' => '/\b(?:wizard-step|data-wizard-|kt_wizard)\b/i',
    'tabs' => '/\b(?:nav-tabs|tab-pane)\b/i',
    'calendarios' => '/\b(?:fullcalendar|calendar)\b/i',
    'estilo_inline' => '/\bstyle\s*=\s*["\x27]/i',
    'blocos_style' => '/<style\b/i',
    'blocos_script' => '/<script\b/i',
    'regras_important' => '/!important/i',
    'sobreposicoes' => '/\bz-index\s*:/i',
    'regras_overflow' => '/\boverflow(?:-[xy])?\s*:/i',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewRoot, FilesystemIterator::SKIP_DOTS)
);

$modules = [];
$files = [];
$featureTotals = array_fill_keys(array_keys($features), 0);
$erpViews = 0;
$otherViews = 0;
$unreadable = [];

foreach ($iterator as $file) {
    if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') {
        continue;
    }

    $absolute = $file->getPathname();
    $source = @file_get_contents($absolute);
    if ($source === false) {
        $unreadable[] = $absolute;
        continue;
    }

    $relative = str_replace('\\', '/', substr($absolute, strlen($root) + 1));
    $parts = explode('/', $relative);
    $module = $parts[2] ?? '_raiz';
    $usesErpShell = preg_match(
        '/@extends\s*\(\s*["\x27]default(?:[.\\\\\/]layout|[.\\\\\/]menu_)/i',
        $source
    ) === 1;

    if ($usesErpShell) {
        $erpViews++;
    } else {
        $otherViews++;
    }

    if (!isset($modules[$module])) {
        $modules[$module] = ['total_views' => 0, 'erp_shell' => 0, 'with_modals' => 0, 'with_forms' => 0, 'with_datatables' => 0];
    }

    $modules[$module]['total_views']++;
    if ($usesErpShell) {
        $modules[$module]['erp_shell']++;
    }

    $counts = [];
    foreach ($features as $feature => $pattern) {
        $matches = preg_match_all($pattern, $source, $ignored);
        $count = $matches === false ? 0 : $matches;
        $counts[$feature] = $count;
        $featureTotals[$feature] += $count;
    }

    if ($counts['modais'] > 0) {
        $modules[$module]['with_modals']++;
    }
    if ($counts['formularios'] > 0) {
        $modules[$module]['with_forms']++;
    }
    if ($counts['datatables'] > 0) {
        $modules[$module]['with_datatables']++;
    }

    $files[] = [
        'path' => $relative,
        'module' => $module,
        'erp_shell' => $usesErpShell,
        'bytes' => strlen($source),
        'features' => array_filter($counts, static function ($count) { return $count > 0; }),
    ];
}

ksort($modules);
usort($files, static function ($a, $b) { return strcmp($a['path'], $b['path']); });

$report = [
    'generated_at' => gmdate('c'),
    'summary' => [
        'blade_views' => count($files),
        'modules' => count($modules),
        'extends_erp_layout' => $erpViews,
        'other_layouts' => $otherViews,
        'unreadable_views' => count($unreadable),
        'feature_occurrences' => $featureTotals,
    ],
    'modules' => $modules,
    'files' => $files,
    'unreadable' => $unreadable,
];

$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    fwrite(STDERR, "Falha ao serializar inventário.\n");
    exit(1);
}

$output = null;
$printJson = false;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--json') {
        $printJson = true;
        continue;
    }
    if (strpos($argument, '--out=') === 0) {
        $output = substr($argument, strlen('--out='));
    }
}

if ($output !== null) {
    if ($output === '') {
        fwrite(STDERR, "Caminho de saída vazio.\n");
        exit(1);
    }

    $target = $output[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\/]/', $output)
        ? $output
        : $root . '/' . $output;
    $directory = dirname($target);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        fwrite(STDERR, "Não foi possível preparar diretório de saída.\n");
        exit(1);
    }
    if (file_put_contents($target, $json . PHP_EOL, LOCK_EX) === false) {
        fwrite(STDERR, "Falha ao gravar inventário JSON.\n");
        exit(1);
    }
    fwrite(STDERR, "Inventário salvo em {$target}\n");
}

if ($printJson) {
    echo $json, PHP_EOL;
    exit(0);
}

printf("Views Blade: %d | Grupos: %d | Usa layout ERP: %d | Outros layouts: %d\n",
    $report['summary']['blade_views'],
    $report['summary']['modules'],
    $erpViews,
    $otherViews
);
echo "Ocorrências identificadas nas views:\n";
foreach ($featureTotals as $name => $quantity) {
    printf("  %-23s %d\n", $name, $quantity);
}
echo "Módulos mais volumosos:\n";
$sorted = $modules;
uasort($sorted, static function ($a, $b) { return $b['total_views'] <=> $a['total_views']; });
foreach (array_slice($sorted, 0, 25, true) as $module => $stats) {
    printf("  %-28s %4d views | %3d no layout ERP | %3d com modais\n",
        $module,
        $stats['total_views'],
        $stats['erp_shell'],
        $stats['with_modals']
    );
}
if (count($unreadable) > 0) {
    fwrite(STDERR, "Aviso: existem views ilegíveis.\n");
    exit(2);
}
