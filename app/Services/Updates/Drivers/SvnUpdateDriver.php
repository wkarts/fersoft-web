<?php

namespace App\Services\Updates\Drivers;

use App\Services\Updates\Contracts\UpdateDriver;
use App\Services\Updates\DTOs\AvailableVersion;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SvnUpdateDriver implements UpdateDriver
{
    protected ?string $repository = null;
    protected ?string $username = null;
    protected ?string $password = null;
    protected ?string $branch = null;

    public function configure(array $credentials = [], array $options = []): void
    {
        $this->repository = Arr::get($credentials, 'repository');
        $this->username = Arr::get($credentials, 'username');
        $this->password = Arr::get($credentials, 'password');
        $this->branch = Arr::get($credentials, 'branch', 'trunk');

        if (! $this->repository) {
            throw new RuntimeException('SVN driver requires repository URL.');
        }
    }

    public function fetchAvailableVersions(?string $currentVersion = null): Collection
    {
        $command = ['svn', 'ls', $this->buildBranchUrl()];
        if ($this->username) {
            $command = array_merge($command, ['--username', $this->username]);
        }
        if ($this->password) {
            $command = array_merge($command, ['--password', $this->password]);
        }

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('SVN ls command failed: '.$process->getErrorOutput());
        }

        $lines = array_filter(explode("\n", trim($process->getOutput())));
        $versions = collect($lines)->map(fn (string $line) => new AvailableVersion(
            identifier: trim($line, '/'),
            description: 'SVN tag/branch',
            releasedAt: Carbon::now(),
        ));

        if ($currentVersion) {
            $versions = $versions->filter(fn (AvailableVersion $version) => version_compare($version->identifier, $currentVersion, '>'));
        }

        return $versions->values();
    }

    public function download(string $version): string
    {
        $url = rtrim($this->repository, '/').'/'.$version;
        $path = storage_path('app/updates/'.Str::slug('svn-'.$version));
        $command = ['svn', 'export', $url, $path, '--force'];

        if ($this->username) {
            $command = array_merge($command, ['--username', $this->username]);
        }
        if ($this->password) {
            $command = array_merge($command, ['--password', $this->password]);
        }

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('SVN export failed: '.$process->getErrorOutput());
        }

        $archivePath = 'updates/'.Str::slug('svn-'.$version).'.zip';
        $zip = new \ZipArchive();
        $zip->open(storage_path('app/'.$archivePath), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($path) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        return $archivePath;
    }

    public function cleanup(string $version): void
    {
        $archivePath = storage_path('app/updates/'.Str::slug('svn-'.$version).'.zip');
        if (file_exists($archivePath)) {
            unlink($archivePath);
        }
    }

    protected function buildBranchUrl(): string
    {
        return rtrim($this->repository, '/').'/'.ltrim($this->branch, '/');
    }
}
