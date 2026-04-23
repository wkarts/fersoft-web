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
        $maxLength = (int) config('fiscal.ver_proc_max_length', 20);
        $appPrefix = $this->normalizePrefix((string) config('fiscal.ver_proc_prefix', ''));

        if ($appPrefix === '') {
            return $this->truncate($version, $maxLength);
        }

        $value = trim($appPrefix . ' ' . $version);
        if ($maxLength <= 0 || strlen($value) <= $maxLength) {
            return $value;
        }

        $remainingForPrefix = $maxLength - strlen($version) - 1;
        if ($remainingForPrefix <= 0) {
            return $this->truncate($version, $maxLength);
        }

        $prefixTruncated = $this->truncate($appPrefix, $remainingForPrefix);

        return trim($prefixTruncated . ' ' . $version);
    }

    private function normalizePrefix(string $prefix): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $prefix));
        if ($normalized === '') {
            return '';
        }

        $maxWords = (int) config('fiscal.ver_proc_prefix_words', 1);
        if ($maxWords <= 0) {
            return '';
        }

        $parts = preg_split('/\s+/', $normalized) ?: [];

        return implode(' ', array_slice($parts, 0, $maxWords));
    }

    private function normalizeVersion(string $version): string
    {
        $trimmedVersion = trim($version);
        $versionToken = trim((string) config('fiscal.ver_proc_version_token', ''));

        if ($trimmedVersion === '' || $versionToken === '') {
            return $trimmedVersion;
        }

        if (str_starts_with(strtolower($trimmedVersion), strtolower($versionToken))) {
            return $trimmedVersion;
        }

        return $versionToken . $trimmedVersion;
    }

    private function truncate(string $value, int $maxLength): string
    {
        if ($maxLength <= 0 || strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength);
    }
}
