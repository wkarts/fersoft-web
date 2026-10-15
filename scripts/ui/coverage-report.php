<?php

/**
 * Inventário técnico automático das superfícies visuais.
 *
 * Executado pelo GitHub Actions, sem banco, sem interação humana,
 * sem qualquer tarefa nas hospedagens dos clientes.
 *
 * Entrada: relatórios gerados por scripts/auditar-tema.php e auditar-assets.php.
 * Saída: JSON e CSV para auxiliar as PRs reais de modernização de código.
 *
 * Não confundir herança de shell/CSS com homologação visual comprovada.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$options = [
    'views' => 'storage/app/tema-inventario.json',
    'assets' => 'storage/app/tema-assets.json',
    'out' => 'storage/app/tema-cobertura.json',
    'csv' => 'storage/app/tema-cobertura.csv',
];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--(views|assets|out|csv)=(.+)$/', $arg, $match)) {
        $options[$match[1]] = $match[2];
    } else {
        fwrite(STDERR, "Argumento inválido: " . $arg . PHP_EOL);
        exit(2);
    }
}

$resolve = static function (string $path) use ($root): string {
    if ($path === '') {
        throw new RuntimeException('Caminho vazio.');
    }
    return $path[0] === '/' ? $path : $root . '/' . $path;
};

$load = static function (string $path) use ($resolve): array {
    $contents = @file_get_contents($resolve($path));
    if ($contents === false) {
        throw new RuntimeException('Inventário indisponível: ' . $path);
    }
    $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !is_array($data['files'] ?? null)) {
        throw new RuntimeException('Formato inválido: ' . $path);
    }
    return $data;
};

$outputPath = static function (string $path) use ($resolve): string {
    $absolute = $resolve($path);
    $dir = dirname($absolute);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Não foi possível preparar: ' . $dir);
    }
    return $absolute;
};

try {
    $views = $load($options['views']);
    $assets = $load($options['assets']);
    if (count($views['files']) !== (int) ($views['summary']['blade_views'] ?? -1)
        || count($assets['files']) !== (int) ($assets['summary']['scanned_assets'] ?? -1)) {
        throw new RuntimeException('Contagem divergente nos inventários; auditoria falhou.');
    }

    $files = [];
    $groups = [];
    $priorities = ['alta' => 0, 'media' => 0, 'baixa' => 0];
    $counters = ['shell_condicional' => 0, 'superficie_independente' => 0, 'layout_a_investigar' => 0, 'asset_legado' => 0, 'outros_assets' => 0];

    $append = static function (array $source, string $type) use (&$files, &$groups, &$priorities, &$counters): void {
        $path = $source['path'] ?? null;
        if (!is_string($path) || $path === '' || str_contains($path, '..') || isset($files[$path])) {
            throw new RuntimeException('Arquivo inválido ou duplicado: ' . (string) $path);
        }

        $markers = $type === 'blade' ? ($source['features'] ?? []) : ($source['markers'] ?? []);
        if (!is_array($markers)) {
            throw new RuntimeException('Indicadores inválidos: ' . $path);
        }
        $group = $type === 'blade'
            ? (string) ($source['module'] ?? '_sem_modulo')
            : (string) ($source['category'] ?? '_sem_categoria');
        $shell = $type === 'blade' && ($source['erp_shell'] ?? false) === true;

        if ($type === 'blade') {
            if ($shell) {
                $integration = 'shell_condicional';
            } elseif (preg_match('~/(?:login|emails?|mails?|pdf|impress[aã]o|publico|public|print)(?:/|[._-])~iu', $path)) {
                $integration = 'superficie_independente';
            } else {
                $integration = 'layout_a_investigar';
            }
            $hasComplexPlugin = (int) ($markers['datatables'] ?? 0) > 0
                || (int) ($markers['wizards'] ?? 0) > 0
                || (int) ($markers['modais'] ?? 0) > 0;
            $priority = $hasComplexPlugin ? 'alta'
                : (((int) ($markers['formularios'] ?? 0) > 0
                || (int) ($markers['select2'] ?? 0) > 0
                || (int) ($markers['estilo_inline'] ?? 0) > 0) ? 'media' : 'baixa');
        } else {
            $integration = $group === 'metronic' ? 'asset_legado' : 'outros_assets';
            $priority = ((int) ($markers['kt_runtime'] ?? 0) > 0
                || (int) ($markers['datatable'] ?? 0) > 0
                || (int) ($markers['jquery'] ?? 0) > 0) ? 'alta'
                : (((int) ($markers['select2'] ?? 0) > 0
                || (int) ($markers['modal'] ?? 0) > 0
                || (int) ($markers['important'] ?? 0) > 0) ? 'media' : 'baixa');
        }

        $groupKey = $type . ':' . $group;
        $groups[$groupKey] ??= [
            'tipo' => $type, 'grupo' => $group, 'arquivos' => 0,
            'alta' => 0, 'media' => 0, 'baixa' => 0,
        ];
        $groups[$groupKey]['arquivos']++;
        $groups[$groupKey][$priority]++;
        $priorities[$priority]++;
        $counters[$integration]++;

        $files[$path] = [
            'path' => $path,
            'tipo' => $type,
            'grupo' => $group,
            'integracao_atual' => $integration,
            'prioridade_tecnica' => $priority,
            'herda_shell_erp' => $shell,
            'indicadores_detectados' => $markers,
            'observacao' => 'Classificação técnica automatizada, não homologação de funcionalidade.',
        ];
    };

    foreach ($views['files'] as $item) {
        if (!is_array($item)) {
            throw new RuntimeException('Entrada Blade inválida.');
        }
        $append($item, 'blade');
    }
    foreach ($assets['files'] as $item) {
        if (!is_array($item)) {
            throw new RuntimeException('Entrada asset inválida.');
        }
        $append($item, 'asset');
    }

    if (count($files) !== count($views['files']) + count($assets['files'])) {
        throw new RuntimeException('Inventário incompleto; possível arquivo duplicado.');
    }

    ksort($files);
    ksort($groups);
    $summary = [
        'blade_views' => count($views['files']),
        'asset_files' => count($assets['files']),
        'total_arquivos' => count($files),
        'categorias_de_integracao' => $counters,
        'prioridades_de_migracao' => $priorities,
        'homologacao_visual' => 'não inferida; comprovada pelos testes das implementações reais',
        'execucao' => 'GitHub Actions; não executar nas hospedagens',
        'exige_preenchimento_manual' => false,
        'bloqueia_promocao_por_planilha' => false,
    ];

    $payload = json_encode([
        'generated_at' => gmdate('c'),
        'summary' => $summary,
        'grupos' => array_values($groups),
        'arquivos' => array_values($files),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    if (file_put_contents($outputPath($options['out']), $payload, LOCK_EX) === false) {
        throw new RuntimeException('Não foi possível escrever relatório JSON.');
    }

    $csv = fopen($outputPath($options['csv']), 'wb');
    if ($csv === false) {
        throw new RuntimeException('Não foi possível escrever relatório CSV.');
    }
    fputcsv($csv, ['arquivo', 'tipo', 'grupo', 'integracao_atual', 'prioridade_tecnica', 'herda_shell', 'indicadores'], ';');
    foreach ($files as $item) {
        fputcsv($csv, [
            $item['path'], $item['tipo'], $item['grupo'],
            $item['integracao_atual'], $item['prioridade_tecnica'],
            $item['herda_shell_erp'] ? 'sim' : 'nao',
            json_encode($item['indicadores_detectados'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], ';');
    }
    fclose($csv);

    printf(
        "Inventário automático: %d Blades, %d assets, %d arquivos; %d com shell; prioridade alta %d, média %d, baixa %d. Nenhuma ação nas hospedagens.\n",
        $summary['blade_views'], $summary['asset_files'], $summary['total_arquivos'],
        $counters['shell_condicional'], $priorities['alta'], $priorities['media'], $priorities['baixa']
    );
} catch (Throwable $error) {
    fwrite(STDERR, 'Falha no inventário técnico: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
