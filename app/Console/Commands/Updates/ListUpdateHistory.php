<?php

namespace App\Console\Commands\Updates;

use App\Models\Updates\UpdateVersion;
use Illuminate\Console\Command;

class ListUpdateHistory extends Command
{
    protected $signature = 'updates:history {--limit=10}';

    protected $description = 'List application update history.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $history = UpdateVersion::query()->latest('created_at')->take($limit)->get();

        if ($history->isEmpty()) {
            $this->info('No update history available.');
            return self::SUCCESS;
        }

        $this->table(['Version', 'Status', 'Applied at'], $history->map(function (UpdateVersion $version) {
            return [
                $version->version,
                $version->status,
                optional($version->applied_at)->toDateTimeString() ?? '-',
            ];
        }));

        return self::SUCCESS;
    }
}
