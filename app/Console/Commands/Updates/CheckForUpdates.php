<?php

namespace App\Console\Commands\Updates;

use App\Services\Updates\UpdateManager;
use Illuminate\Console\Command;

class CheckForUpdates extends Command
{
    protected $signature = 'updates:check {--provider=} {--current=}';

    protected $description = 'Check for updates using the configured update provider.';

    public function handle(UpdateManager $updateManager): int
    {
        $provider = $this->option('provider');
        $current = $this->option('current') ?? $updateManager->currentVersion()?->version;

        $versions = $updateManager->fetchAvailableVersions($current, $provider);

        if ($versions->isEmpty()) {
            $this->info('No updates available.');
            return self::SUCCESS;
        }

        $this->table(['Version', 'Description', 'Released at'], $versions->map(function ($version) {
            return [
                $version->identifier,
                $version->description,
                optional($version->releasedAt)->toDateTimeString() ?? '-'
            ];
        }));

        return self::SUCCESS;
    }
}
