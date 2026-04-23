<?php

namespace App\Support;

use App\Services\AppVersionService;

class FiscalProcessVersion
{
    public function __construct(private AppVersionService $appVersionService)
    {
    }

    public function verProc(): string
    {
        $version = $this->normalizeVersion($this->appVersionService->resolveInstalledVersion());
        $appPrefix = trim((string) config('fiscal.ver_proc_prefix', ''));

        if ($appPrefix === '') {
            return $version;
        }

        return trim($appPrefix . ' ' . $version);
    }

    private function normalizeVersion(string $version): string
    {
        $trimmedVersion = trim($version);
        $versionToken = trim((string) config('fiscal.ver_proc_version_token', 'v'));

        if ($trimmedVersion === '' || $versionToken === '') {
            return $trimmedVersion;
        }

        if (str_starts_with(strtolower($trimmedVersion), strtolower($versionToken))) {
            return $trimmedVersion;
        }

        return $versionToken . $trimmedVersion;
    }
}
