<?php

namespace App\Services;

use App\Models\AppVersion;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class AppVersionService
{
    public function current(): ?AppVersion
    {
        return AppVersion::query()
            ->where('is_current', true)
            ->orderByDesc('installed_at')
            ->orderByDesc('id')
            ->first();
    }

    public function history(?int $limit = null): Collection
    {
        return AppVersion::query()
            ->orderByDesc('version_major')
            ->orderByDesc('version_minor')
            ->orderByDesc('version_patch')
            ->orderByDesc('installed_at')
            ->orderByDesc('id')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    public function groupedHistoryByMajor(?int $limit = null): Collection
    {
        return $this->history($limit)
            ->groupBy('version_major')
            ->sortKeysDesc()
            ->map(function (Collection $items) {
                return $items
                    ->sort(function (AppVersion $a, AppVersion $b) {
                        return [$b->version_major, $b->version_minor, $b->version_patch, (string) $b->installed_at]
                            <=>
                            [$a->version_major, $a->version_minor, $a->version_patch, (string) $a->installed_at];
                    })
                    ->values();
            });
    }

    public function isSemanticVersion(string $version): bool
    {
        return $this->normalizeSemanticVersion($version) !== null;
    }

    public function writeComposerVersion(string $version): bool
    {
        $normalizedVersion = $this->normalizeSemanticVersion($version);
        if ($normalizedVersion === null) {
            return false;
        }

        $composerPath = base_path('composer.json');
        if (!File::exists($composerPath)) {
            return false;
        }

        $raw = File::get($composerPath);
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            return false;
        }

        $data['version'] = $normalizedVersion;
        File::put($composerPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        return true;
    }

    public function currentOrFallback(): AppVersion
    {
        $current = $this->current();
        if ($current) {
            if (empty(trim((string) $current->commit_hash))) {
                $current->commit_hash = $this->resolveInstalledRevision();
            }
            return $current;
        }

        $this->syncFromArtifacts();

        $current = $this->current();
        if ($current) {
            if (empty(trim((string) $current->commit_hash))) {
                $current->commit_hash = $this->resolveInstalledRevision();
            }
            return $current;
        }

        $version = $this->resolveInstalledVersion();
        [$major, $minor, $patch] = $this->splitVersion($version);

        return new AppVersion([
            'version' => $version,
            'version_major' => $major,
            'version_minor' => $minor,
            'version_patch' => $patch,
            'title' => 'Versão instalada (fallback)',
            'is_current' => true,
            'commit_hash' => $this->resolveInstalledRevision(),
        ]);
    }

    public function resolveInstalledVersion(): string
    {
        $composerPath = base_path('composer.json');
        if (is_file($composerPath)) {
            $raw = safe_file_get_contents($composerPath);
            $json = json_decode((string) $raw, true);
            $composerVersion = $this->normalizeSemanticVersion((string) data_get($json, 'version', ''));
            if ($composerVersion !== null) {
                return $composerVersion;
            }
        }

        $latestArtifactVersion = $this->detectLatestArtifactVersion();
        if ($latestArtifactVersion !== null) {
            return $latestArtifactVersion;
        }

        $envVersion = $this->normalizeSemanticVersion((string) (env('APPVERSION') ?: env('VERSION') ?: env('APP_VERSION') ?: env('RELEASE_VERSION') ?: config('app.version')));
        if ($envVersion !== null) {
            return $envVersion;
        }

        $gitVersion = $this->detectGitVersion();
        if ($gitVersion !== null) {
            return $gitVersion;
        }

        return '0.0.0';
    }

    public function resolveInstalledRevision(): ?string
    {
        $envRevision = $this->normalizeRevision((string) (
        env('APPREVISION')
            ?: env('APP_REVISION')
            ?: env('RELEASE_REVISION')
                ?: env('COMMIT_HASH')
                    ?: env('GIT_COMMIT')
                        ?: env('GITHUB_SHA')
                            ?: config('app.revision')
        ));

        if ($envRevision !== null) {
            return $envRevision;
        }

        $manifestRevision = $this->detectManifestCurrentRevision();
        if ($manifestRevision !== null) {
            return $manifestRevision;
        }

        $gitRevision = $this->detectGitCommitHash();
        if ($gitRevision !== null) {
            return $gitRevision;
        }

        return null;
    }

    public function syncFromArtifacts(?string $installedVersion = null): int
    {
        $installedVersion = $installedVersion ?: $this->resolveInstalledVersion();
        $installedRevision = $this->resolveInstalledRevision();

        $versions = $this->artifactVersions();
        $upserts = 0;

        foreach ($versions as $version) {
            [$major, $minor, $patch] = $this->splitVersion($version);
            $base = 'releases/'.$version;

            $currentHtmlPath = Storage::disk('local')->exists($base.'/current.html') ? $base.'/current.html' : null;
            $cumulativeHtmlPath = Storage::disk('local')->exists($base.'/cumulative.html') ? $base.'/cumulative.html' : null;
            $currentPdfPath = Storage::disk('local')->exists($base.'/current.pdf') ? $base.'/current.pdf' : null;
            $cumulativePdfPath = Storage::disk('local')->exists($base.'/cumulative.pdf') ? $base.'/cumulative.pdf' : null;

            $currentHtml = $currentHtmlPath ? Storage::disk('local')->get($currentHtmlPath) : null;
            $cumulativeHtml = $cumulativeHtmlPath ? Storage::disk('local')->get($cumulativeHtmlPath) : null;

            $payload = [
                'version_major' => $major,
                'version_minor' => $minor,
                'version_patch' => $patch,
                'title' => 'Release '.$version,
                'release_notes_current_html' => $currentHtml,
                'release_notes_cumulative_html' => $cumulativeHtml,
                'release_notes_current_html_path' => $currentHtmlPath,
                'release_notes_cumulative_html_path' => $cumulativeHtmlPath,
                'release_notes_current_pdf_path' => $currentPdfPath,
                'release_notes_cumulative_pdf_path' => $cumulativePdfPath,
                'release_notes_html' => $currentHtml,
                'release_notes_pdf_path' => $cumulativePdfPath,
                'release_notes_format' => $this->resolveFormat($currentHtml, $cumulativePdfPath),
                'installed_at' => now(),
            ];

            if ($version === $installedVersion && $installedRevision !== null) {
                $payload['commit_hash'] = $installedRevision;
            }

            $record = AppVersion::query()->updateOrCreate(
                ['version' => $version],
                $payload
            );

            $upserts += $record->wasRecentlyCreated ? 1 : 0;
        }

        if ($installedVersion !== '') {
            AppVersion::query()->where('is_current', true)->update(['is_current' => false]);
            AppVersion::query()->where('version', $installedVersion)->update(['is_current' => true]);

            if ($installedRevision !== null) {
                AppVersion::query()
                    ->where('version', $installedVersion)
                    ->update(['commit_hash' => $installedRevision]);
            }
        }

        return $upserts;
    }

    private function artifactVersions(): Collection
    {
        $dirs = collect(Storage::disk('local')->directories('releases'))
            ->map(fn (string $dir) => basename($dir))
            ->filter(fn (string $version) => preg_match('/^v?\d+\.\d+\.\d+$/', $version))
            ->map(fn (string $version) => ltrim($version, 'vV'));

        return $dirs
            ->sort(function ($a, $b) {
                return version_compare($b, $a);
            })
            ->values();
    }

    private function detectLatestArtifactVersion(): ?string
    {
        return $this->artifactVersions()->first();
    }

    private function detectGitVersion(): ?string
    {
        if (!is_dir(base_path('.git'))) {
            return null;
        }

        $commands = [
            ['git', 'describe', '--tags', '--exact-match'],
            ['git', 'tag', '--list', 'v[0-9]*.[0-9]*.[0-9]*', '--sort=-version:refname'],
        ];

        foreach ($commands as $command) {
            $process = new Process($command, base_path());
            $process->setTimeout(3);
            $process->run();

            if (!$process->isSuccessful()) {
                continue;
            }

            $output = trim($process->getOutput());
            if ($output === '') {
                continue;
            }

            $firstLine = trim(strtok($output, "\n") ?: '');
            $version = $this->normalizeSemanticVersion($firstLine);
            if ($version !== null) {
                return $version;
            }
        }

        return null;
    }

    private function detectGitCommitHash(): ?string
    {
        if (!is_dir(base_path('.git'))) {
            return null;
        }

        $commands = [
            ['git', 'rev-parse', '--short', 'HEAD'],
            ['git', 'rev-parse', 'HEAD'],
        ];

        foreach ($commands as $command) {
            $process = new Process($command, base_path());
            $process->setTimeout(3);
            $process->run();

            if (!$process->isSuccessful()) {
                continue;
            }

            $output = trim($process->getOutput());
            $revision = $this->normalizeRevision($output);

            if ($revision !== null) {
                return $revision;
            }
        }

        return null;
    }

    private function detectManifestCurrentRevision(): ?string
    {
        $manifestPath = storage_path('app/releases/manifest.json');
        if (!is_file($manifestPath)) {
            return null;
        }

        $raw = safe_file_get_contents($manifestPath);
        $manifest = json_decode((string) $raw, true);

        if (!is_array($manifest)) {
            return null;
        }

        $currentVersion = ltrim((string) Arr::get($manifest, 'current_version', ''), 'vV');
        $currentVersion = $this->normalizeSemanticVersion($currentVersion);

        $releases = collect(Arr::get($manifest, 'releases', []));
        if ($currentVersion === null || $releases->isEmpty()) {
            return null;
        }

        $release = $releases->first(function ($item) use ($currentVersion) {
            if (!is_array($item)) {
                return false;
            }

            $version = ltrim((string) Arr::get($item, 'version', ''), 'vV');
            $version = $this->normalizeSemanticVersion($version);

            return $version === $currentVersion;
        });

        if (!is_array($release)) {
            return null;
        }

        return $this->normalizeRevision((string) Arr::get($release, 'commit_hash'));
    }

    public function syncFromManifest(?string $manifestPath = null): int
    {
        $manifestPath = $manifestPath ?: storage_path('app/releases/manifest.json');
        if (!is_file($manifestPath)) {
            return 0;
        }

        $raw = safe_file_get_contents($manifestPath);
        $manifest = json_decode((string) $raw, true);
        if (!is_array($manifest)) {
            return 0;
        }

        $releases = collect(Arr::get($manifest, 'releases', []));
        if ($releases->isEmpty()) {
            return 0;
        }

        $currentVersion = ltrim((string) Arr::get($manifest, 'current_version', ''), 'vV');
        $currentVersion = $this->normalizeSemanticVersion($currentVersion) ?? '';
        $upserts = 0;

        foreach ($releases as $release) {
            if (!is_array($release)) {
                continue;
            }

            $version = ltrim((string) Arr::get($release, 'version', ''), 'vV');
            $version = $this->normalizeSemanticVersion($version);
            if ($version === null) {
                continue;
            }

            [$major, $minor, $patch] = $this->splitVersion($version);

            $record = AppVersion::query()->updateOrCreate(
                ['version' => $version],
                [
                    'version_major' => $major,
                    'version_minor' => $minor,
                    'version_patch' => $patch,
                    'title' => Arr::get($release, 'title', 'Release '.$version),
                    'release_notes_current_html' => Arr::get($release, 'current_html'),
                    'release_notes_cumulative_html' => Arr::get($release, 'cumulative_html'),
                    'release_notes_current_html_path' => Arr::get($release, 'current_html_path'),
                    'release_notes_cumulative_html_path' => Arr::get($release, 'cumulative_html_path'),
                    'release_notes_current_pdf_path' => Arr::get($release, 'current_pdf_path'),
                    'release_notes_cumulative_pdf_path' => Arr::get($release, 'cumulative_pdf_path'),
                    'release_notes_html' => Arr::get($release, 'current_html'),
                    'release_notes_pdf_path' => Arr::get($release, 'cumulative_pdf_path'),
                    'release_notes_format' => Arr::get($release, 'format', $this->resolveFormat(Arr::get($release, 'current_html'), Arr::get($release, 'cumulative_pdf_path'))),
                    'released_at' => Arr::get($release, 'released_at'),
                    'installed_at' => Arr::get($release, 'installed_at', now()),
                    'build_number' => Arr::get($release, 'build_number'),
                    'commit_hash' => $this->normalizeRevision((string) Arr::get($release, 'commit_hash')),
                    'release_channel' => Arr::get($release, 'release_channel'),
                    'author' => Arr::get($release, 'author'),
                    'observations' => Arr::get($release, 'observations'),
                    'metadata' => Arr::get($release, 'metadata', []),
                    'is_current' => false,
                ]
            );

            $upserts += $record->wasRecentlyCreated ? 1 : 0;
        }

        if ($currentVersion !== '') {
            AppVersion::query()->where('is_current', true)->update(['is_current' => false]);
            AppVersion::query()->where('version', $currentVersion)->update(['is_current' => true]);
        }

        return $upserts;
    }

    public function register(array $data): AppVersion
    {
        if ((bool) Arr::get($data, 'bootstrap_from_artifacts', true)) {
            $this->syncFromArtifacts(Arr::get($data, 'installed_version_for_sync'));
        }

        return DB::transaction(function () use ($data) {
            $version = (string) Arr::get($data, 'version');
            [$major, $minor, $patch] = $this->splitVersion($version);

            $currentHtmlInput = $this->normalizeReleaseCurrentHtml(
                Arr::get($data, 'release_notes_current_html', Arr::get($data, 'release_notes_html')),
                (string) Arr::get($data, 'title', "Release {$version}"),
                $version,
                Arr::get($data, 'observations')
            );

            $isCurrent = (bool) Arr::get($data, 'is_current', true);
            if ($isCurrent) {
                AppVersion::query()->where('is_current', true)->update(['is_current' => false]);
            }

            $metadata = Arr::get($data, 'metadata', []);
            if (!is_array($metadata)) {
                $metadata = [];
            }

            $record = AppVersion::query()->updateOrCreate(
                ['version' => $version],
                [
                    'version_major' => Arr::get($data, 'version_major', $major),
                    'version_minor' => Arr::get($data, 'version_minor', $minor),
                    'version_patch' => Arr::get($data, 'version_patch', $patch),
                    'title' => Arr::get($data, 'title'),
                    'release_notes_current_html' => $currentHtmlInput,
                    'release_notes_html' => $currentHtmlInput,
                    'is_current' => $isCurrent,
                    'released_at' => Arr::get($data, 'released_at'),
                    'installed_at' => Arr::get($data, 'installed_at', now()),
                    'build_number' => Arr::get($data, 'build_number'),
                    'commit_hash' => $this->normalizeRevision((string) Arr::get($data, 'commit_hash')) ?: $this->resolveInstalledRevision(),
                    'release_channel' => Arr::get($data, 'release_channel'),
                    'author' => Arr::get($data, 'author'),
                    'observations' => Arr::get($data, 'observations'),
                    'metadata' => $metadata,
                ]
            );

            $record->refresh();

            $history = AppVersion::query()
                ->orderByDesc('version_major')
                ->orderByDesc('version_minor')
                ->orderByDesc('version_patch')
                ->orderByDesc('installed_at')
                ->orderByDesc('id')
                ->get();

            $cumulativeHtml = $this->buildCumulativeHtml($record, $history);

            $paths = $this->persistHtmlFiles($record->version, $currentHtmlInput, $cumulativeHtml);
            $pdfPaths = $this->persistPdfFiles($record->version, $currentHtmlInput, $cumulativeHtml, !(bool) Arr::get($data, 'skip_pdf', false));

            $format = $this->resolveFormat($currentHtmlInput, $pdfPaths['cumulative_pdf_path']);

            $record->fill([
                'release_notes_cumulative_html' => $cumulativeHtml,
                'release_notes_current_html_path' => $paths['current_html_path'],
                'release_notes_cumulative_html_path' => $paths['cumulative_html_path'],
                'release_notes_current_pdf_path' => $pdfPaths['current_pdf_path'],
                'release_notes_cumulative_pdf_path' => $pdfPaths['cumulative_pdf_path'],
                'release_notes_pdf_path' => $pdfPaths['cumulative_pdf_path'],
                'release_notes_format' => $format,
            ]);

            $metadata = $record->metadata ?? [];
            if (!is_array($metadata)) {
                $metadata = [];
            }

            $metadata['generated_at'] = now()->toIso8601String();
            $metadata['artifacts'] = [
                'current_html' => $paths['current_html_path'],
                'cumulative_html' => $paths['cumulative_html_path'],
                'current_pdf' => $pdfPaths['current_pdf_path'],
                'cumulative_pdf' => $pdfPaths['cumulative_pdf_path'],
            ];

            if ($pdfPaths['pdf_engine'] === null) {
                $metadata['pdf_warning'] = 'Engine de PDF indisponível no ambiente. HTML gerado normalmente.';
            }

            $record->metadata = $metadata;
            $record->save();

            return $record->fresh();
        });
    }

    private function splitVersion(string $version): array
    {
        $parts = explode('.', ltrim($version, 'vV'));

        return [
            (int) ($parts[0] ?? 0),
            (int) ($parts[1] ?? 0),
            (int) preg_replace('/\D.*/', '', (string) ($parts[2] ?? '0')),
        ];
    }

    private function normalizeSemanticVersion(string $version): ?string
    {
        $normalized = ltrim(trim($version), 'vV');

        if ($normalized === '' || !preg_match('/^\d+\.\d+\.\d+$/', $normalized)) {
            return null;
        }

        return $normalized;
    }

    private function normalizeRevision(string $revision): ?string
    {
        $revision = trim($revision);

        if ($revision === '' || $revision === '-') {
            return null;
        }

        return preg_replace('/\s+/', '', $revision);
    }

    private function normalizeReleaseCurrentHtml(?string $html, string $title, string $version, ?string $observations): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            $obs = trim((string) $observations);
            $obsBlock = $obs !== '' ? '<p><strong>Observações:</strong> '.$this->escapeHtml($obs).'</p>' : '<p>Sem notas detalhadas informadas.</p>';

            return "<h2>{$this->escapeHtml($title)}</h2><p><strong>Versão:</strong> {$this->escapeHtml($version)}</p>{$obsBlock}";
        }

        return $html;
    }

    private function buildCumulativeHtml(AppVersion $currentVersion, Collection $history): string
    {
        $header = '<h1>Release Notes Cumulativa</h1>'
            .'<p><strong>Aplicação:</strong> '.config('app.name').'</p>'
            .'<p><strong>Versão atual:</strong> '.$this->escapeHtml($currentVersion->version).'</p>'
            .'<p><strong>Gerado em:</strong> '.now()->format('d/m/Y H:i:s').'</p>'
            .'<p><strong>Canal:</strong> '.$this->escapeHtml((string) ($currentVersion->release_channel ?: '-')).'</p>'
            .'<p><strong>Build:</strong> '.$this->escapeHtml((string) ($currentVersion->build_number ?: '-')).'</p>'
            .'<p><strong>Commit:</strong> '.$this->escapeHtml((string) ($currentVersion->commit_hash ?: '-')).'</p>'
            .'<p><strong>Autor:</strong> '.$this->escapeHtml((string) ($currentVersion->author ?: '-')).'</p>';

        $sections = $history->map(function (AppVersion $version) {
            $notes = $version->release_notes_current_html
                ?: $version->release_notes_html
                    ?: '<p>'.$this->escapeHtml((string) ($version->observations ?: 'Sem conteúdo de release notes.')).'</p>';

            return '<hr>'
                .'<h2>Versão '.$this->escapeHtml($version->version).'</h2>'
                .'<p><strong>Título:</strong> '.$this->escapeHtml((string) ($version->title ?: '-')).'</p>'
                .'<p><strong>Release:</strong> '.optional($version->released_at)->format('d/m/Y H:i').'</p>'
                .'<p><strong>Instalação:</strong> '.optional($version->installed_at)->format('d/m/Y H:i').'</p>'
                .'<div>'.$notes.'</div>';
        })->implode(PHP_EOL);

        return '<html><head><meta charset="utf-8"></head><body>'.$header.$sections.'</body></html>';
    }

    private function persistHtmlFiles(string $version, string $currentHtml, string $cumulativeHtml): array
    {
        $baseDir = 'releases/'.$version;
        $currentPath = $baseDir.'/current.html';
        $cumulativePath = $baseDir.'/cumulative.html';

        Storage::disk('local')->put($currentPath, $currentHtml);
        Storage::disk('local')->put($cumulativePath, $cumulativeHtml);

        return [
            'current_html_path' => $currentPath,
            'cumulative_html_path' => $cumulativePath,
        ];
    }

    private function persistPdfFiles(string $version, string $currentHtml, string $cumulativeHtml, bool $generate): array
    {
        if (!$generate || !class_exists(Dompdf::class)) {
            return [
                'current_pdf_path' => null,
                'cumulative_pdf_path' => null,
                'pdf_engine' => null,
            ];
        }

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $currentPdfPath = 'releases/'.$version.'/current.pdf';
        $cumulativePdfPath = 'releases/'.$version.'/cumulative.pdf';

        Storage::disk('local')->put($currentPdfPath, $this->renderPdf($currentHtml, $options));
        Storage::disk('local')->put($cumulativePdfPath, $this->renderPdf($cumulativeHtml, $options));

        return [
            'current_pdf_path' => $currentPdfPath,
            'cumulative_pdf_path' => $cumulativePdfPath,
            'pdf_engine' => 'dompdf',
        ];
    }

    private function renderPdf(string $html, Options $options): string
    {
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }

    private function resolveFormat(?string $html, ?string $pdfPath): string
    {
        $hasHtml = !empty(trim((string) $html));
        $hasPdf = !empty(trim((string) $pdfPath));

        if ($hasHtml && $hasPdf) {
            return 'html_pdf';
        }

        if ($hasHtml) {
            return 'html';
        }

        if ($hasPdf) {
            return 'pdf';
        }

        return 'none';
    }

    private function escapeHtml(string $value): string
    {
        return e($value);
    }
}
