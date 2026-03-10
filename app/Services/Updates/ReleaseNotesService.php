<?php

namespace App\Services\Updates;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ReleaseNotesService
{
    private const FILE_PATH = 'docs/release-notes.json';

    public function __construct(private UpdateManager $updateManager)
    {
    }

    public function installedRelease(): array
    {
        $currentVersion = $this->updateManager->currentVersion();
        $version = $currentVersion?->version ?? config('app.version') ?? '0.0.0';

        $release = $this->findByVersion($version);
        [$major, $minor, $patch] = $this->splitVersion($version);

        return [
            'version' => $version,
            'major' => $major,
            'minor' => $minor,
            'patch' => $patch,
            'release_date' => Arr::get($release, 'release_date')
                ?? optional($currentVersion?->released_at)->toDateString()
                ?? optional($currentVersion?->applied_at)->toDateString(),
            'cpf' => Arr::get($release, 'cpf', 'Não informado'),
            'location' => Arr::get($release, 'location', 'Não informado'),
            'highlights' => Arr::get($release, 'highlights', []),
            'author' => Arr::get($release, 'author', []),
        ];
    }

    public function releases(): array
    {
        return Arr::get($this->readData(), 'releases', []);
    }

    private function findByVersion(string $version): ?array
    {
        foreach ($this->releases() as $release) {
            if (Arr::get($release, 'version') === $version) {
                return $release;
            }
        }

        return null;
    }

    private function splitVersion(string $version): array
    {
        $normalized = ltrim($version, 'vV');
        $segments = explode('.', $normalized);

        return [
            (int) ($segments[0] ?? 0),
            (int) ($segments[1] ?? 0),
            (int) preg_replace('/\D.*/', '', (string) ($segments[2] ?? '0')),
        ];
    }

    private function readData(): array
    {
        $path = base_path(self::FILE_PATH);

        if (! File::exists($path)) {
            return [];
        }

        $raw = File::get($path);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
