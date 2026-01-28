<?php

namespace App\Console\Commands;

use App\Models\SystemUpdate;
use App\Services\SystemUpdateManager;
use Illuminate\Console\Command;

class SystemRollbackRun extends Command
{
    protected $signature = 'system:rollback {updateId} {--restore-db} {--restore-dump}';

    protected $description = 'Realiza rollback seguro de um update registrado';

    public function handle(SystemUpdateManager $manager): int
    {
        $update = SystemUpdate::findOrFail($this->argument('updateId'));
        $options = [
            'restore_db' => $this->option('restore-db'),
            'restore_dump' => $this->option('restore-dump'),
        ];

        $manager->rollback($update, $options);
        $this->info("Rollback disparado para update #{$update->id}");

        return self::SUCCESS;
    }
}
