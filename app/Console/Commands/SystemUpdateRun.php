<?php

namespace App\Console\Commands;

use App\Services\SystemUpdateManager;
use Illuminate\Console\Command;

class SystemUpdateRun extends Command
{
    protected $signature = 'system:update {--reference=} {--file=*} {--dry-run} {--no-backup} {--no-composer} {--no-migrations} {--initiated-by=}';

    protected $description = 'Executa o update conforme modo configurado para a instância';

    public function handle(SystemUpdateManager $manager): int
    {
        $options = [
            'dry_run' => $this->option('dry-run'),
            'mysql_db_backup' => ! $this->option('no-backup'),
            'composer_update' => ! $this->option('no-composer'),
            'migrations_update' => ! $this->option('no-migrations'),
            'initiated_by' => $this->option('initiated-by') ?: 'cli',
        ];

        $reference = $this->option('reference');
        $files = $this->option('file');

        $update = $manager->run($reference, $files, $options);
        $this->info("Update registrado #{$update->id} em modo {$update->mode}");

        return self::SUCCESS;
    }
}
