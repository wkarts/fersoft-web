<?php

namespace App\Console\Commands\Updates;

use App\Services\Updates\UpdateManager;
use Illuminate\Console\Command;

class ApplyUpdate extends Command
{
    protected $signature = 'updates:apply {version} {--provider=} {--downgrade}';

    protected $description = 'Schedule an application update.';

    public function handle(UpdateManager $updateManager): int
    {
        $version = $this->argument('version');
        $provider = $this->option('provider');
        $downgrade = (bool) $this->option('downgrade');

        if ($downgrade && ! config('updates.allow_downgrade')) {
            $this->error('Downgrade is disabled. Enable UPDATE_ALLOW_DOWNGRADE to allow it.');
            return self::FAILURE;
        }

        $record = $updateManager->scheduleUpdate($version, $provider, $downgrade);

        $this->info("Update {$record->version} scheduled with status {$record->status}.");

        return self::SUCCESS;
    }
}
