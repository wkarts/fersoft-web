<?php

namespace App\Console\Commands;

use App\Services\AppVersionService;
use Illuminate\Console\Command;

class SyncAppVersionFromArtifactsCommand extends Command
{
    protected $signature = 'app-version:sync {--version= : Versão instalada atual (opcional)} {--manifest= : Caminho para manifest.json de releases}';

    protected $description = 'Sincroniza app_versions com artifacts em storage/app/releases e define versão atual';

    public function handle(AppVersionService $service): int
    {
        $installedVersion = trim((string) $this->option('version')) ?: $service->resolveInstalledVersion();
        $manifestPath = trim((string) $this->option('manifest')) ?: null;

        $createdFromManifest = $service->syncFromManifest($manifestPath);
        $createdFromArtifacts = $service->syncFromArtifacts($installedVersion);

        $this->info('Sincronização concluída.');
        $this->line('Versão atual aplicada: '.$installedVersion);
        $this->line('Novos registros via manifest: '.$createdFromManifest);
        $this->line('Novos registros via artifacts: '.$createdFromArtifacts);

        return self::SUCCESS;
    }
}
