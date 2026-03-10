<?php

namespace App\Services;

use App\Models\AppVersion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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

    public function history(int $limit = 30)
    {
        return AppVersion::query()
            ->orderByDesc('installed_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function register(array $data): AppVersion
    {
        return DB::transaction(function () use ($data) {
            $version = (string) Arr::get($data, 'version');
            [$major, $minor, $patch] = $this->splitVersion($version);

            $releaseNotesHtml = Arr::get($data, 'release_notes_html');
            $pdfPath = Arr::get($data, 'release_notes_pdf_path');
            $format = $this->resolveFormat($releaseNotesHtml, $pdfPath);

            if ((bool) Arr::get($data, 'is_current', true)) {
                AppVersion::query()->where('is_current', true)->update(['is_current' => false]);
            }

            $record = AppVersion::query()->updateOrCreate(
                ['version' => $version],
                [
                    'version_major' => Arr::get($data, 'version_major', $major),
                    'version_minor' => Arr::get($data, 'version_minor', $minor),
                    'version_patch' => Arr::get($data, 'version_patch', $patch),
                    'title' => Arr::get($data, 'title'),
                    'release_notes_html' => $releaseNotesHtml,
                    'release_notes_pdf_path' => $pdfPath,
                    'release_notes_format' => Arr::get($data, 'release_notes_format', $format),
                    'is_current' => (bool) Arr::get($data, 'is_current', true),
                    'released_at' => Arr::get($data, 'released_at'),
                    'installed_at' => Arr::get($data, 'installed_at', now()),
                    'build_number' => Arr::get($data, 'build_number'),
                    'commit_hash' => Arr::get($data, 'commit_hash'),
                    'release_channel' => Arr::get($data, 'release_channel'),
                    'author' => Arr::get($data, 'author'),
                    'observations' => Arr::get($data, 'observations'),
                    'metadata' => Arr::get($data, 'metadata', []),
                ]
            );

            return $record;
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
}
