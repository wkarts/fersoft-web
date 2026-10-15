<?php

/**
 * Matriz de cobertura do redesenho a partir das auditorias existentes.
 *
 * php scripts/ui/coverage-report.php
 * php scripts/ui/coverage-report.php --require-complete  # gate final, NAO usar na beta
 *
 * Nunca deduz "homologado" por presenca de CSS ou heranca do layout.
 * Nao altera views, assets, bancos ou permissoes.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$options = [
    'views' => 'storage/app/tema-inventario.json',
    'assets' => 'storage/app/tema-assets.json',
    'decisions' => 'docs/ui/coverage-reviews.json',
    'out' => 'storage/app/tema-cobertura.json',
    'csv' => 'storage/app/tema-cobertura.csv',
];
$strict = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--require-complete') {
        $strict = true;
    } elseif (preg_match('/^--(views|assets|decisions|out|csv)=(.+)$/', $arg, $match)) {
        $options[$match[1]] = $match[2];
    } else {
        fwrite(STDERR, "Argumento invalido: {$arg}\n");
        exit(2);
    }
}

$pathOf = static fn (string $p): string =>
    $p[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\/]/', $p) ? $p : $root . '/' . $p;

$readJson = static function (string $path) use ($pathOf): array {
    $contents = @file_get_contents($pathOf($path));
    if ($contents === false) {
        throw new RuntimeException("Nao foi possivel ler: {$path}");
    }
    $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) {
        throw new RuntimeException("Esperado objeto JSON: {$path}");
    }
    return $data;
};

$prepare = static function (string $path) use ($pathOf): string {
    $file = $pathOf($path);
    if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0775, true)
        && !is_dir(dirname($file))) {
        throw new RuntimeException("Diretorio de saida indisponivel: {$path}");
    }
    return $file;
};

try {
    $views = $readJson($options['views']);
    $assets = $readJson($options['assets']);
    $reviews = $readJson($options['decisions']);
    if (!is_array($views['files'] ?? null) || !isset($views['summary']['blade_views'])
        || !is_array($assets['files'] ?? null) || !isset($assets['summary']['scanned_assets'])
        || ($reviews['version'] ?? null) !== 1 || !is_array($reviews['entries'] ?? null)) {
        throw new RuntimeException('Formatos de inventario/revisao invalidos.');
    }

    $records = [];
    $groups = [];
    $add = static function (array $item, string $type) use (&$records, &$groups): void {
        $path = $item['path'] ?? null;
        if (!is_string($path) || $path === '' || str_contains($path, '..')
            || isset($records[$path])) {
            throw new RuntimeException('Arquivo invalido/duplicado: ' . (string) $path);
        }
        $group = $type === 'blade'
            ? (string) ($item['module'] ?? '_sem_grupo')
            : (string) ($item['category'] ?? '_sem_categoria');
        $groupKey = $type . ':' . $group;
        $groups[$groupKey] ??= [
            'tipo' => $type, 'grupo' => $group, 'total' => 0,
            'pendente' => 0, 'homologado' => 0, 'dispensado_justificado' => 0,
        ];
        $groups[$groupKey]['total']++;
        $groups[$groupKey]['pendente']++;
        $records[$path] = [
            'path' => $path,
            'tipo' => $type,
            'grupo' => $group,
            'herda_shell_erp' => $type === 'blade' && (bool) ($item['erp_shell'] ?? false),
            'indicadores' => $type === 'blade' ? ($item['features'] ?? []) : ($item['markers'] ?? []),
            'situacao' => 'pendente',
            'responsavel' => null,
            'evidencia' => null,
            'justificativa' => null,
        ];
    };
    foreach ($views['files'] as $view) {
        if (!is_array($view)) {
            throw new RuntimeException('Item invalido no inventario de Blade.');
        }
        $add($view, 'blade');
    }
    foreach ($assets['files'] as $asset) {
        if (!is_array($asset)) {
            throw new RuntimeException('Item invalido no inventario de assets.');
        }
        $add($asset, 'asset');
    }

    if (count($views['files']) !== (int) $views['summary']['blade_views']
        || count($assets['files']) !== (int) $assets['summary']['scanned_assets']
        || count($records) !== count($views['files']) + count($assets['files'])) {
        throw new RuntimeException('Contagens inconsistentes no inventario.');
    }

    foreach ($reviews['entries'] as $path => $decision) {
        if (!isset($records[$path])) {
            throw new RuntimeException("Revisao obsoleta (arquivo ausente): {$path}");
        }
        if (!is_array($decision)) {
            throw new RuntimeException("Revisao mal formada: {$path}");
        }
        $state = $decision['situacao'] ?? null;
        $owner = trim((string) ($decision['responsavel'] ?? ''));
        $evidence = trim((string) ($decision['evidencia'] ?? ''));
        $reason = trim((string) ($decision['justificativa'] ?? ''));
        if (!in_array($state, ['homologado', 'dispensado_justificado'], true)
            || $owner === '' || $evidence === ''
            || ($state === 'dispensado_justificado' && $reason === '')) {
            throw new RuntimeException("Sem situacao/responsavel/evidencia/justificativa: {$path}");
        }
        $records[$path]['situacao'] = $state;
        $records[$path]['responsavel'] = $owner;
        $records[$path]['evidencia'] = $evidence;
        $records[$path]['justificativa'] = $reason ?: null;
        $key = $records[$path]['tipo'] . ':' . $records[$path]['grupo'];
        $groups[$key]['pendente']--;
        $groups[$key][$state]++;
    }

    ksort($records);
    ksort($groups);
    $pending = count(array_filter($records, static fn (array $r): bool => $r['situacao'] === 'pendente'));
    $approved = count(array_filter($records, static fn (array $r): bool => $r['situacao'] === 'homologado'));
    $exempt = count($records) - $pending - $approved;
    $summary = [
        'blade_views' => count($views['files']),
        'asset_files' => count($assets['files']),
        'arquivos_total' => count($records),
        'blades_que_herdam_shell_erp' => count(array_filter(
            $views['files'], static fn (array $v): bool => (bool) ($v['erp_shell'] ?? false)
        )),
        'pendente' => $pending,
        'homologado' => $approved,
        'dispensado_justificado' => $exempt,
        'completude_homologada' => $pending === 0,
        'observacao' => 'Heranca CSS NAO e homologacao; arquivos aprovados exigem evidencia.',
    ];
    $report = [
        'generated_at' => gmdate('c'),
        'summary' => $summary,
        'grupos' => array_values($groups),
        'arquivos' => array_values($records),
    ];
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    if (file_put_contents($prepare($options['out']), $json, LOCK_EX) === false) {
        throw new RuntimeException('Falha gravando matriz JSON.');
    }

    $csv = fopen($prepare($options['csv']), 'wb');
    if ($csv === false) {
        throw new RuntimeException('Falha abrindo matriz CSV.');
    }
    fputcsv($csv, [
        'path', 'tipo', 'grupo', 'herda_shell_erp', 'situacao', 'responsavel',
        'evidencia', 'justificativa', 'indicadores',
    ], ';');
    foreach ($records as $row) {
        fputcsv($csv, [
            $row['path'], $row['tipo'], $row['grupo'],
            $row['herda_shell_erp'] ? 'sim' : 'nao',
            $row['situacao'], $row['responsavel'] ?? '', $row['evidencia'] ?? '',
            $row['justificativa'] ?? '',
            json_encode($row['indicadores'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], ';');
    }
    fclose($csv);

    printf(
        "Cobertura: %d Blades + %d assets = %d; %d herdados (NAO homologados); %d homologados; %d dispensados; %d pendentes\n",
        $summary['blade_views'], $summary['asset_files'], $summary['arquivos_total'],
        $summary['blades_que_herdam_shell_erp'], $approved, $exempt, $pending
    );
    if ($strict && $pending > 0) {
        fwrite(STDERR, "HOMOLOGACAO INCOMPLETA: {$pending} arquivos pendentes.\n");
        exit(3);
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Falha na matriz de cobertura: ' . $error->getMessage() . "\n");
    exit(1);
}
