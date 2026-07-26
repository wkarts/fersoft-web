<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define o agendamento dos comandos da aplicação.
     */
    protected function schedule(Schedule $schedule): void
    {
        /*
        |--------------------------------------------------------------------------
        | Monitoramento do Scheduler
        |--------------------------------------------------------------------------
        |
        | Registra que o Scheduler está ativo.
        |
        | Quando executado pelo cron do servidor, a origem será "server".
        | Quando executado pela rota interna protegida, a origem poderá ser
        | injetada como "internal_http" no container da aplicação.
        |
        */

        $schedule->command('cron:heartbeat')
            ->everyMinute()
            ->withoutOverlapping(2);

        /*
        |--------------------------------------------------------------------------
        | Empresas e usuários online
        |--------------------------------------------------------------------------
        */

        $schedule->command('empresas_logada:cron')
            ->everyMinute()
            ->withoutOverlapping(5);

        /*
        |--------------------------------------------------------------------------
        | Documentos fiscais eletrônicos
        |--------------------------------------------------------------------------
        |
        | O comando é disparado a cada 10 minutos.
        | A regra interna mantém o intervalo/trava de aproximadamente
        | 65 minutos por empresa ou filial.
        |
        */

        $schedule->command('dfe:cron')
            ->everyTenMinutes()
            ->withoutOverlapping(70);

        /*
        |--------------------------------------------------------------------------
        | Cashback
        |--------------------------------------------------------------------------
        */

        $schedule->command('cash-back:cron')
            ->dailyAt('08:00')
            ->withoutOverlapping(30);

        /*
        |--------------------------------------------------------------------------
        | Tarefas
        |--------------------------------------------------------------------------
        */

        $schedule->command('tarefas:lembrete-5min')
            ->everyMinute()
            ->withoutOverlapping(5);

        $schedule->command('tarefas:gerar-recorrentes')
            ->dailyAt('01:00')
            ->withoutOverlapping(60);

        $schedule->command('tarefas:dedo-duro')
            ->everyTenMinutes()
            ->withoutOverlapping(15);

        /*
        |--------------------------------------------------------------------------
        | Sincronização automática de NFS-e
        |--------------------------------------------------------------------------
        |
        | Executa diariamente às 08:00 e às 18:00.
        |
        | runInBackground:
        | impede que uma sincronização longa bloqueie o início dos demais
        | comandos agendados pelo Scheduler.
        |
        | withoutOverlapping:
        | impede que uma nova sincronização seja iniciada enquanto a
        | execução anterior ainda estiver ativa.
        |
        */

        $schedule->command('nfse:sincronizar-automatica')
            ->twiceDaily(8, 18)
            ->withoutOverlapping(180)
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/cron_nfse.log')
            );
    }

    /**
     * Registra os comandos Artisan da aplicação.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}