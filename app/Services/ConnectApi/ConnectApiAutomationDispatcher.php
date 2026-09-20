<?php

namespace App\Services\ConnectApi;

use App\Http\Controllers\PontoWhatsAppController;
use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\ConnectApiWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConnectApiAutomationDispatcher
{
    public function dispatch(ConnectApiWebhookEvent $event): void
    {
        $normalized = $event->normalized_payload ?? [];

        if (($normalized['event'] ?? '') === 'messages-upsert') {
            $this->dispatchPointCompatibility($event);
        }

        $this->dispatchConfiguredRules($event);
    }

    private function dispatchPointCompatibility(ConnectApiWebhookEvent $event): void
    {
        try {
            $payload = $event->payload ?? [];
            $request = Request::create(
                '/api/webhooks/connect-api/point-compatibility',
                'POST',
                $payload
            );

            app(PontoWhatsAppController::class)->receberMensagem($request);
        } catch (\Throwable $e) {
            Log::error('Falha na automação Connect|API -> Ponto.', [
                'event_id' => $event->id,
                'empresa_id' => $event->empresa_id,
                'erro' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function dispatchConfiguredRules(ConnectApiWebhookEvent $event): void
    {
        $trigger = 'connect-api.' . ($event->normalized_payload['event'] ?? $event->event_type);

        $rules = AutomationRule::query()
            ->where('enabled', true)
            ->where('trigger', $trigger)
            ->where(function ($query) use ($event) {
                $query->whereNull('empresa_id')
                    ->orWhere('empresa_id', $event->empresa_id);
            })
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            AutomationExecution::create([
                'automation_rule_id' => $rule->id,
                'empresa_id' => $event->empresa_id,
                'trigger' => $trigger,
                'connect_api_webhook_event_id' => $event->id,
                'status' => 'registered',
                'started_at' => now(),
                'finished_at' => now(),
                'context' => [
                    'conditions' => $rule->conditions,
                    'actions' => $rule->actions,
                    'event' => $event->normalized_payload,
                ],
            ]);

            if ($rule->stop_on_success) {
                break;
            }
        }
    }
}
