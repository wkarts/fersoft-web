<?php

namespace App\Console\Commands\Updater;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ApplyAliasCommand extends Command
{
    protected $signature = 'updater:apply {version} {--provider=} {--downgrade}';

    protected $description = 'Alias legado para updates:apply';

    public function handle(): int
    {
        $exitCode = Artisan::call('updates:apply', [
            'version' => $this->argument('version'),
            '--provider' => $this->option('provider'),
            '--downgrade' => (bool)$this->option('downgrade'),
        ]);

        $this->line(Artisan::output());

        return $exitCode;
    }
}
