<?php

namespace App\Jobs;

use App\Models\ConnectApiWebhookEvent;
use App\Services\ConnectApi\ConnectApiAutomationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessConnectApiWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(public readonly int $eventId)
    {
    }

    public function handle(ConnectApiAutomationDispatcher $dispatcher): void
    {
        $event = ConnectApiWebhookEvent::findOrFail($this->eventId);

        if ($event->status === 'processed') {
            return;
        }

        $event->status = 'processing';
        $event->processing_at = now();
        $event->attempts = ((int) $event->attempts) + 1;
        $event->save();

        try {
            $dispatcher->dispatch($event);

            $event->status = 'processed';
            $event->processed_at = now();
            $event->last_error = null;
            $event->save();
        } catch (\Throwable $e) {
            $event->status = $this->attempts() >= $this->tries ? 'failed' : 'retry';
            $event->last_error = $e->getMessage();
            $event->next_retry_at = now()->addSeconds(
                $this->backoff[min(max($this->attempts() - 1, 0), count($this->backoff) - 1)]
            );
            $event->save();

            throw $e;
        }
    }
}
