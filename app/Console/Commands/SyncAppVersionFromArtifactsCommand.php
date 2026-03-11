<?php

namespace App\Console\Commands;

use App\Services\AppVersionService;
use Illuminate\Console\Command;

class SyncAppVersionFromArtifactsCommand extends Command
{
    protected $signature = 'app-version:sync {--version= : Versão instalada atual (opcional)} {--manifest= : Caminho para manifest.json de releases} {--write-composer-version : Atualiza composer.json com a versão aplicada}';

    protected $description = 'Sincroniza app_versions com artifacts em storage/app/releases e define versão atual';

    public function handle(AppVersionService $service): int
    {
        $installedVersion = trim((string) $this->option('version')) ?: $service->resolveInstalledVersion();
        if (!$service->isSemanticVersion($installedVersion)) {
            $this->error("Versão inválida: {$installedVersion}. Use formato semântico x.y.z.");
            return self::FAILURE;
        }

        $manifestPath = trim((string) $this->option('manifest')) ?: null;

        $createdFromManifest = $service->syncFromManifest($manifestPath);
        $createdFromArtifacts = $service->syncFromArtifacts($installedVersion);

        $this->info('Sincronização concluída.');
        $this->line('Versão atual aplicada: '.$installedVersion);
        $this->line('Novos registros via manifest: '.$createdFromManifest);
        $this->line('Novos registros via artifacts: '.$createdFromArtifacts);
        if ((bool) $this->option('write-composer-version')) {
            if (!$service->writeComposerVersion($installedVersion)) {
                $this->warn('Falha ao atualizar composer.json com a versão aplicada.');
            } else {
                $this->line('composer.json atualizado para versão: '.$installedVersion);
            }
        }

        return self::SUCCESS;
    }
}
