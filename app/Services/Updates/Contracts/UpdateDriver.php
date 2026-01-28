<?php

namespace App\Services\Updates\Contracts;

use App\Services\Updates\DTOs\AvailableVersion;
use Illuminate\Support\Collection;

interface UpdateDriver
{
    public function configure(array $credentials = [], array $options = []): void;

    /**
     * @return Collection<int, AvailableVersion>
     */
    public function fetchAvailableVersions(?string $currentVersion = null): Collection;

    public function download(string $version): string;

    public function cleanup(string $version): void;
}
