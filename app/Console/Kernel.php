<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Heartbeat do Scheduler.
        // Quando o schedule:run roda pelo cron do servidor, a origem fica como "server".
        // Quando roda pela rota interna protegida, a rota injeta "internal_http" no container.
        $schedule->command('cron:heartbeat')
            ->everyMinute()
            ->withoutOverlapping(2);

        // Atualiza totalizador de empresas/usuários online.
        $schedule->command('empresas_logada:cron')
            ->everyMinute()
            ->withoutOverlapping(5);

        // Robô DF-e: executa a cada 10 minutos, mas a regra interna mantém trava de 65 minutos por empresa/filial.
        $schedule->command('dfe:cron')
            ->everyTenMinutes()
            ->withoutOverlapping(70);

        // Cashback: mensagens automáticas diárias.
        $schedule->command('cash-back:cron')
            ->dailyAt('08:00')
            ->withoutOverlapping(30);

        // Tarefas: lembretes, recorrência e aviso de atraso.
        $schedule->command('tarefas:lembrete-5min')
            ->everyMinute()
            ->withoutOverlapping(5);

        $schedule->command('tarefas:gerar-recorrentes')
            ->dailyAt('01:00')
            ->withoutOverlapping(60);

        $schedule->command('tarefas:dedo-duro')
            ->everyTenMinutes()
            ->withoutOverlapping(15);

        // $schedule->command('cash-back:cron')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
