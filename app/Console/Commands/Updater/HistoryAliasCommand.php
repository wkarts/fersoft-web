<?php

namespace App\Console\Commands\Updater;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class HistoryAliasCommand extends Command
{
    protected $signature = 'updater:history {--limit=10}';

    protected $description = 'Alias legado para updates:history';

    public function handle(): int
    {
        $exitCode = Artisan::call('updates:history', [
            '--limit' => $this->option('limit'),
        ]);

        $this->line(Artisan::output());

        return $exitCode;
    }
}
