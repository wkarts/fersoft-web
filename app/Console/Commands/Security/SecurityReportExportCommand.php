<?php

namespace App\Console\Commands\Security;

use App\Services\Security\SecurityReportExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SecurityReportExportCommand extends Command
{
    protected $signature = 'seguranca:relatorio-exportar
        {report=health : Relatório: health, companies, resources, permissions, protections, authorizers, tokens, audit-policies, operations}
        {--empresa= : ID da empresa para filtrar}
        {--format=csv : Formato: csv ou json}
        {--path= : Caminho relativo em storage/app para salvar o arquivo}';

    protected $description = 'Exporta relatórios administrativos da Segurança de Operações em CSV ou JSON.';

    public function handle(SecurityReportExportService $reportService): int
    {
        $report = (string) $this->argument('report');
        $empresaId = $this->option('empresa') ? (int) $this->option('empresa') : null;
        $format = strtolower((string) $this->option('format'));

        if (!in_array($format, ['csv', 'json'], true)) {
            $this->error('Formato inválido. Use csv ou json.');
            return self::FAILURE;
        }

        $rows = $reportService->build($report, $empresaId);
        $filename = $reportService->filename($report, $format, $empresaId);
        $path = $this->option('path') ?: ('security_reports/' . $filename);

        $content = $format === 'json'
            ? json_encode([
                'report' => $report,
                'empresa_id' => $empresaId,
                'generated_at' => now()->toDateTimeString(),
                'rows' => $rows,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : $reportService->toCsv($rows);

        Storage::disk('local')->put($path, $content);

        $this->info('Relatório exportado com sucesso.');
        $this->line(storage_path('app/' . $path));
        $this->line('Registros: ' . count($rows));

        return self::SUCCESS;
    }
}
