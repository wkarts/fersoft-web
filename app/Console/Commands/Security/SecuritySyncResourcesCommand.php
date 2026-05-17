<?php

namespace App\Console\Commands\Security;

use App\Services\Security\SecurityCrudResourceScannerService;
use Illuminate\Console\Command;

class SecuritySyncResourcesCommand extends Command
{
    protected $signature = 'seguranca:recursos-sincronizar
        {--json : Retorna o resultado em JSON}
        {--show-errors : Exibe detalhes dos erros de scanner, quando houver}';

    protected $description = 'Sincroniza recursos de Segurança de Operações a partir das Models que herdam BaseModel';

    public function handle(SecurityCrudResourceScannerService $scanner): int
    {
        $stats = $scanner->sync();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return empty($stats['errors']) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Sincronização de recursos concluída.');
        $this->line('Models varridas: ' . (int) ($stats['scanned'] ?? 0));
        $this->line('Recursos sincronizados: ' . (int) ($stats['synced'] ?? 0));
        $this->line('Ignorados: ' . (int) ($stats['skipped'] ?? 0));
        $this->line('Erros: ' . count($stats['errors'] ?? []));

        if ((bool) $this->option('show-errors') && !empty($stats['errors'])) {
            $this->newLine();
            $this->warn('Erros encontrados:');
            foreach ($stats['errors'] as $error) {
                $this->line('- ' . ($error['model'] ?? 'Model desconhecida') . ': ' . ($error['error'] ?? 'Erro não informado'));
            }
        }

        return empty($stats['errors']) ? self::SUCCESS : self::FAILURE;
    }
}
