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
        /*
         * ============================================================
         * HEARTBEAT DO SCHEDULER
         * ============================================================
         *
         * Quando o schedule:run roda pelo cron do servidor,
         * a origem fica como "server".
         *
         * Quando roda pela rota interna protegida,
         * a rota injeta "internal_http" no container.
         */
        $schedule->command('cron:heartbeat')
            ->everyMinute()
            ->withoutOverlapping(2);


        /*
         * ============================================================
         * EMPRESAS / USUÁRIOS ONLINE
         * ============================================================
         */
        $schedule->command('empresas_logada:cron')
            ->everyMinute()
            ->withoutOverlapping(5);


        /*
         * ============================================================
         * DF-e
         * ============================================================
         *
         * Executa a cada 10 minutos.
         *
         * A regra interna do comando mantém trava de 65 minutos
         * por empresa/filial.
         */
        $schedule->command('dfe:cron')
            ->everyTenMinutes()
            ->withoutOverlapping(70);


        /*
         * ============================================================
         * CASHBACK
         * ============================================================
         */
        $schedule->command('cash-back:cron')
            ->dailyAt('08:00')
            ->withoutOverlapping(30);


        /*
         * ============================================================
         * TAREFAS
         * ============================================================
         */

        // Lembrete de tarefa próxima.
        $schedule->command('tarefas:lembrete-5min')
            ->everyMinute()
            ->withoutOverlapping(5);

        // Geração de tarefas recorrentes.
        $schedule->command('tarefas:gerar-recorrentes')
            ->dailyAt('01:00')
            ->withoutOverlapping(60);

        // Alerta de atraso para supervisores.
        $schedule->command('tarefas:dedo-duro')
            ->everyTenMinutes()
            ->withoutOverlapping(15);


        /*
         * ============================================================
         * FROTA
         * ============================================================
         */

        // Inbox persistente do webhook Traccar. Reprocessa falhas transitórias
        // antes do robô principal consumir as etapas classificadas.
        $schedule->command('traccar:webhooks:processar --limit=100')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground();

        $schedule->command('frota:monitorar')
            ->everyMinute()
            ->withoutOverlapping(5);

        /*
         * Reconciliação complementar: recupera do próprio Traccar eventos
         * que possam não ter chegado pelo Event Forwarding (queda de rede,
         * reinício do ERP, proxy indisponível etc.). A dedupe_key garante
         * que eventos já recebidos pelo webhook não sejam repetidos.
         */
        $schedule->command('traccar:eventos:reconciliar')
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->runInBackground();

        // Limpeza somente de eventos antigos já finalizados.
        $schedule->command('traccar:webhooks:processar --prune')
            ->dailyAt('03:30')
            ->withoutOverlapping(10);


        /*
         * ============================================================
         * ATUALIZAÇÃO DE COORDENADAS
         * ============================================================
         *
         * Atualiza coordenadas dos cadastros a cada 5 minutos.
         */
        $schedule->command('cadastros:atualizar-coordenadas')
            ->everyFiveMinutes()
            ->withoutOverlapping(10);


        /*
         * ============================================================
         * NFS-e - SINCRONIZAÇÃO AUTOMÁTICA
         * ============================================================
         *
         * Executa duas vezes ao dia:
         *
         * 08:00
         * 18:00
         *
         * runInBackground permite que os demais eventos do Scheduler
         * continuem sendo executados enquanto a sincronização ocorre.
         */
        $schedule->command('nfse:sincronizar-automatica')
            ->twiceDaily(8, 18)
            ->withoutOverlapping(600)
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/cron_nfse.log')
            );


        /*
         * ============================================================
         * PONTO - LEMBRETES DE PENDÊNCIAS
         * ============================================================
         */

        /*
         * Alerta de almoço.
         *
         * Segunda a sexta-feira às 13:00.
         */
        $schedule->command('ponto:verificar-pendencias')
            ->weekdays()
            ->at('13:00')
            ->withoutOverlapping(120)
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/cron_ponto.log')
            );


        /*
         * Alerta de saída / fim do expediente.
         *
         * Segunda a sexta-feira às 18:30.
         */
        $schedule->command('ponto:verificar-pendencias')
            ->weekdays()
            ->at('18:30')
            ->withoutOverlapping(120)
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/cron_ponto.log')
            );


        /*
         * Para testes:
         *
         * $schedule->command('cash-back:cron')->everyMinute();
         */
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
