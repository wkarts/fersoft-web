<?php

namespace App\Console\Commands;

use App\Services\Cron\CronMonitor;
use Illuminate\Console\Command;

class CronHeartbeatCommand extends Command
{
    protected $signature = 'cron:heartbeat {source? : Origem do cron: server ou internal_http}';

    protected $description = 'Registra heartbeat do Laravel Scheduler para detectar se o cron do servidor ou interno está ativo.';

    public function handle(CronMonitor $monitor): int
    {
        $source = (string) ($this->argument('source') ?: '');

        if ($source === '' && app()->bound('fersoft.cron_source')) {
            $source = (string) app('fersoft.cron_source');
        }

        if ($source === '') {
            $source = 'server';
        }

        $payload = $monitor->markSchedulerRun($source, [
            'command' => 'cron:heartbeat',
        ]);

        $this->info('Cron heartbeat registrado: ' . $payload['source'] . ' em ' . $payload['ran_at']);

        return self::SUCCESS;
    }
}
