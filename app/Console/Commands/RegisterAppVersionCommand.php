<?php

namespace App\Console\Commands;

use App\Services\AppVersionService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class RegisterAppVersionCommand extends Command
{
    protected $signature = 'app-version:register
        {version : Versão semântica (ex.: 2.4.1)}
        {--title= : Título da release}
        {--notes-html= : Conteúdo HTML de release notes}
        {--notes-html-file= : Arquivo .html com release notes}
        {--notes-pdf= : Caminho do PDF de release notes}
        {--released-at= : Data de release (Y-m-d H:i:s)}
        {--installed-at= : Data de instalação (Y-m-d H:i:s)}
        {--build-number= : Build number}
        {--commit-hash= : Hash do commit}
        {--release-channel= : Canal (stable, beta, hotfix)}
        {--author=Wallace Kleiton <wkarts@gmail.com> : Autor da release}
        {--observations= : Observações}
        {--metadata= : JSON com metadados extras}
        {--no-current : Não marca esta versão como atual}';

    protected $description = 'Registra uma nova versão no controle interno da aplicação';

    public function handle(AppVersionService $service): int
    {
        $version = (string) $this->argument('version');
        $html = $this->option('notes-html');
        $htmlFile = $this->option('notes-html-file');

        if ($htmlFile) {
            if (!File::exists($htmlFile)) {
                $this->error("Arquivo HTML não encontrado: {$htmlFile}");
                return self::FAILURE;
            }
            $html = File::get($htmlFile);
        }

        $metadata = $this->parseMetadata((string) $this->option('metadata'));

        $payload = [
            'version' => $version,
            'title' => $this->option('title'),
            'release_notes_html' => $html,
            'release_notes_pdf_path' => $this->option('notes-pdf'),
            'released_at' => $this->option('released-at') ?: null,
            'installed_at' => $this->option('installed-at') ?: now(),
            'build_number' => $this->option('build-number'),
            'commit_hash' => $this->option('commit-hash'),
            'release_channel' => $this->option('release-channel'),
            'author' => $this->option('author'),
            'observations' => $this->option('observations'),
            'metadata' => $metadata,
            'is_current' => !$this->option('no-current'),
        ];

        $record = $service->register($payload);

        $this->info("Versão {$record->version} registrada com sucesso.");
        $this->line('ID: '.$record->id);
        $this->line('Atual instalada: '.($record->is_current ? 'sim' : 'não'));
        $this->line('Formato release notes: '.$record->release_notes_format);

        return self::SUCCESS;
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
