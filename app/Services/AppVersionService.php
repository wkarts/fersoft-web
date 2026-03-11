<?php

namespace App\Services;

use App\Models\AppVersion;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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


    public function currentOrFallback(): AppVersion
    {
        $current = $this->current();
        if ($current) {
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
        ]);
    }

    public function resolveInstalledVersion(): string
    {
        $composerPath = base_path('composer.json');
        if (is_file($composerPath)) {
            $raw = file_get_contents($composerPath);
            $json = json_decode((string) $raw, true);
            $composerVersion = trim((string) data_get($json, 'version', ''));
            if ($composerVersion !== '') {
                return ltrim($composerVersion, 'vV');
            }
        }

        $envVersion = trim((string) (env('VERSION') ?: env('APP_VERSION') ?: config('app.version')));
        if ($envVersion !== '') {
            return ltrim($envVersion, 'vV');
        }

        return '0.0.0';
    }

    public function register(array $data): AppVersion
    {
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
                    'commit_hash' => Arr::get($data, 'commit_hash'),
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
                ?: '<p>'. $this->escapeHtml((string) ($version->observations ?: 'Sem conteúdo de release notes.')) .'</p>';

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
