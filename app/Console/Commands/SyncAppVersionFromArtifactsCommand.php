<?php

namespace App\Console\Commands;

use App\Services\AppVersionService;
use Illuminate\Console\Command;

class SyncAppVersionFromArtifactsCommand extends Command
{
    protected $signature = 'app-version:sync {--version= : Versão instalada atual (opcional)}';

    protected $description = 'Sincroniza app_versions com artifacts em storage/app/releases e define versão atual';

    public function handle(AppVersionService $service): int
    {
        $installedVersion = trim((string) $this->option('version')) ?: $service->resolveInstalledVersion();
        $created = $service->syncFromArtifacts($installedVersion);

        $this->info('Sincronização concluída.');
        $this->line('Versão atual aplicada: '.$installedVersion);
        $this->line('Novos registros criados: '.$created);

        return self::SUCCESS;
    }
}
