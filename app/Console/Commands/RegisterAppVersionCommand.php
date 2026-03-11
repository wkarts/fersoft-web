<?php

namespace App\Console\Commands;

use App\Services\AppVersionService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class RegisterAppVersionCommand extends Command
{
    protected $signature = 'app-version:register
        {version? : Versão semântica (ex.: 2.4.1). Se omitida, tenta composer.json/env}
        {--title= : Título da release}
        {--notes-html= : [LEGADO] Conteúdo HTML da release atual}
        {--notes-html-file= : [LEGADO] Arquivo HTML da release atual}
        {--notes-current-html= : Conteúdo HTML da release atual}
        {--notes-current-html-file= : Arquivo HTML da release atual}
        {--released-at= : Data de release (Y-m-d H:i:s)}
        {--installed-at= : Data de instalação (Y-m-d H:i:s)}
        {--build-number= : Build number}
        {--commit-hash= : Hash do commit}
        {--release-channel= : Canal (stable, beta, hotfix)}
        {--author=Wallace Kleiton <wkarts@gmail.com> : Autor da release}
        {--observations= : Observações}
        {--metadata= : JSON com metadados extras}
        {--skip-pdf : Não tenta gerar PDF de release notes}
        {--use-composer-version : Força uso da versão do composer/env}
        {--no-bootstrap-artifacts : Não sincroniza artifacts antigos antes de gerar cumulativa}
        {--no-current : Não marca esta versão como atual}';

    protected $description = 'Registra versão interna e gera release notes atual + cumulativa (HTML/PDF)';

    public function handle(AppVersionService $service): int
    {
        $version = $this->resolveVersion($service);
        $currentHtml = $this->resolveCurrentHtml();
        if ($currentHtml === false) {
            return self::FAILURE;
        }

        $metadata = $this->parseMetadata((string) $this->option('metadata'));

        $payload = [
            'version' => $version,
            'title' => $this->option('title'),
            'release_notes_current_html' => $currentHtml,
            'release_notes_html' => $currentHtml,
            'released_at' => $this->option('released-at') ?: null,
            'installed_at' => $this->option('installed-at') ?: now(),
            'build_number' => $this->option('build-number'),
            'commit_hash' => $this->option('commit-hash'),
            'release_channel' => $this->option('release-channel'),
            'author' => $this->option('author'),
            'observations' => $this->option('observations'),
            'metadata' => $metadata,
            'is_current' => !$this->option('no-current'),
            'skip_pdf' => (bool) $this->option('skip-pdf'),
            'bootstrap_from_artifacts' => !(bool) $this->option('no-bootstrap-artifacts'),
            'installed_version_for_sync' => $version,
        ];

        $record = $service->register($payload);

        $this->info("Versão {$record->version} registrada com sucesso.");
        $this->line('ID: '.$record->id);
        $this->line('Atual instalada: '.($record->is_current ? 'sim' : 'não'));
        $this->line('Formato release notes: '.$record->release_notes_format);
        $this->line('HTML atual: '.($record->release_notes_current_html_path ?: '-'));
        $this->line('HTML cumulativo: '.($record->release_notes_cumulative_html_path ?: '-'));
        $this->line('PDF cumulativo: '.($record->release_notes_cumulative_pdf_path ?: '-'));

        return self::SUCCESS;
    }


    private function resolveVersion(AppVersionService $service): string
    {
        $inputVersion = trim((string) $this->argument('version'));

        if ($inputVersion !== '' && !$this->option('use-composer-version')) {
            return $inputVersion;
        }

        return $service->resolveInstalledVersion();
    }

    /**
     * @return string|false
     */
    private function resolveCurrentHtml()
    {
        $inline = $this->option('notes-current-html') ?? $this->option('notes-html');
        $file = $this->option('notes-current-html-file') ?? $this->option('notes-html-file');

        if ($file) {
            if (!File::exists($file)) {
                $this->error("Arquivo HTML não encontrado: {$file}");
                return false;
            }

            return File::get($file);
        }

        return $inline;
    }

    private function parseMetadata(string $metadata): array
    {
        if (trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        if (!is_array($decoded)) {
            $this->warn('Metadata inválido. Ignorando conteúdo informado.');
            return [];
        }

        return Arr::where($decoded, fn ($value) => !is_null($value));
    }
}
