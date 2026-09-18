<?php

namespace App\Console\Commands;

use App\Services\TraccarWebhookService;
use Illuminate\Console\Command;
use Throwable;

class ProcessarTraccarWebhooks extends Command
{
    protected $signature = 'traccar:webhooks:processar
                            {--limit=100 : Quantidade máxima de eventos por execução}
                            {--prune : Remove eventos antigos já finalizados}';

    protected $description = 'Reprocessa de forma idempotente eventos pendentes do webhook Traccar';

    public function handle(TraccarWebhookService $service): int
    {
        try {
            if ($this->option('prune')) {
                $removidos = $service->podarEventosAntigos();
                $this->info("Eventos antigos removidos: {$removidos}");

                return self::SUCCESS;
            }

            $limit = max(1, min(1000, (int) $this->option('limit')));
            $resultado = $service->reprocessarPendentes($limit);

            $this->info(
                'Traccar webhook: ' . json_encode(
                    $resultado,
                    JSON_UNESCAPED_UNICODE
                )
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Falha ao reprocessar webhooks Traccar: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
