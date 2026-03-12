<?php

namespace App\Console\Commands;

use App\Services\AppVersionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ApplyReleaseVersionCommand extends Command
{
    protected $signature = 'app-version:apply-release
        {version : Versão semântica da release (x.y.z)}
        {--manifest= : Caminho para manifest.json de releases}
        {--title= : Título da release atual}
        {--notes-current-html= : HTML da release atual}
        {--notes-current-html-file= : Arquivo HTML da release atual}
        {--released-at= : Data de release (Y-m-d H:i:s)}
        {--installed-at= : Data de instalação (Y-m-d H:i:s)}
        {--build-number= : Build number}
        {--commit-hash= : Hash do commit}
        {--release-channel=stable : Canal (stable, beta, hotfix)}
        {--author=Wallace Kleiton <wkarts@gmail.com> : Autor da release}
        {--observations= : Observações}
        {--skip-pdf : Não tenta gerar PDF de release notes}
        {--skip-register : Apenas sincroniza e atualiza composer, sem registrar release atual}
        {--no-bootstrap-artifacts : Não sincroniza artifacts antigos antes de gerar cumulativa}';

    protected $description = 'Aplica versão de release no composer.json e sincroniza/registre app_versions para deploy';

    public function handle(AppVersionService $service): int
    {
        $version = trim((string) $this->argument('version'));
        if (!$service->isSemanticVersion($version)) {
            $this->error("Versão inválida: {$version}. Use formato semântico x.y.z.");
            return self::FAILURE;
        }

        if (!$service->writeComposerVersion($version)) {
            $this->error('Falha ao atualizar composer.json com a versão da release.');
            return self::FAILURE;
        }

        $manifestPath = trim((string) $this->option('manifest')) ?: null;
        $createdFromManifest = $service->syncFromManifest($manifestPath);
        $createdFromArtifacts = $service->syncFromArtifacts($version);

        $this->line('composer.json atualizado para versão: '.$version);
        $this->line('Novos registros via manifest: '.$createdFromManifest);
        $this->line('Novos registros via artifacts: '.$createdFromArtifacts);

        if ((bool) $this->option('skip-register')) {
            $this->info('Fluxo concluído sem registro explícito da release atual (--skip-register).');
            return self::SUCCESS;
        }

        $currentHtml = $this->resolveCurrentHtml();
        if ($currentHtml === false) {
            return self::FAILURE;
        }

        $record = $service->register([
            'version' => $version,
            'title' => $this->option('title') ?: 'Release '.$version,
            'release_notes_current_html' => $currentHtml,
            'release_notes_html' => $currentHtml,
            'released_at' => $this->option('released-at') ?: null,
            'installed_at' => $this->option('installed-at') ?: now(),
            'build_number' => $this->option('build-number'),
            'commit_hash' => $this->option('commit-hash'),
            'release_channel' => $this->option('release-channel'),
            'author' => $this->option('author'),
            'observations' => $this->option('observations'),
            'is_current' => true,
            'skip_pdf' => (bool) $this->option('skip-pdf'),
            'bootstrap_from_artifacts' => !(bool) $this->option('no-bootstrap-artifacts'),
            'installed_version_for_sync' => $version,
        ]);

        $this->info("Versão {$record->version} aplicada com sucesso.");

        return self::SUCCESS;
    }

    /**
     * @return string|false
     */
    private function resolveCurrentHtml()
    {
        $inline = $this->option('notes-current-html');
        $file = $this->option('notes-current-html-file');

        if ($file) {
            if (!File::exists($file)) {
                $this->error("Arquivo HTML não encontrado: {$file}");
                return false;
            }

            return File::get($file);
        }

        return $inline;
    }
}
