<?php

namespace App\Services\Updates\Drivers;

use App\Services\Updates\Contracts\UpdateDriver;
use App\Services\Updates\DTOs\AvailableVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FilesystemUpdateDriver implements UpdateDriver
{
    protected ?string $path = null;

    public function configure(array $credentials = [], array $options = []): void
    {
        $this->path = $options['path'] ?? $credentials['path'] ?? null;

        if (! $this->path) {
            throw new RuntimeException('Filesystem driver requires a path configuration.');
        }
    }

    public function fetchAvailableVersions(?string $currentVersion = null): Collection
    {
        $disk = Storage::disk('local');
        $directories = collect($disk->directories($this->path ?? ''));

        $versions = $directories->map(function (string $directory) use ($disk) {
            $parts = explode('/', $directory);
            $version = end($parts);

            return new AvailableVersion(
                identifier: $version,
                description: 'Local filesystem package',
                releasedAt: Carbon::createFromTimestamp($disk->lastModified($directory)),
            );
        });

        if ($currentVersion) {
            $versions = $versions->filter(fn (AvailableVersion $version) => version_compare($version->identifier, $currentVersion, '>'));
        }

        return $versions->values();
    }

    public function download(string $version): string
    {
        $disk = Storage::disk('local');
        $source = Str::finish($this->path ?? '', '/').$version.'.zip';
        if (! $disk->exists($source)) {
            throw new RuntimeException("Local update archive [{$source}] not found.");
        }

        $target = 'updates/'.Str::slug('filesystem-'.$version).'.zip';
        $disk->copy($source, $target);

        return $target;
    }

    public function cleanup(string $version): void
    {
        // Filesystem driver does not delete source files; cleanup handled by manager if necessary.
    }
}
