<?php

namespace App\Console\Commands\Updater;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CheckAliasCommand extends Command
{
    protected $signature = 'updater:check {--provider=} {--current=}';

    protected $description = 'Alias legado para updates:check';

    public function handle(): int
    {
        $exitCode = Artisan::call('updates:check', [
            '--provider' => $this->option('provider'),
            '--current' => $this->option('current'),
        ]);

        $this->line(Artisan::output());

        return $exitCode;
    }
}
