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
            $execution = AutomationExecution::create([
                'automation_rule_id' => $rule->id,
                'empresa_id' => $event->empresa_id,
                'trigger' => $trigger,
                'connect_api_webhook_event_id' => $event->id,
                'status' => 'processing',
                'started_at' => now(),
                'context' => [
                    'conditions' => $rule->conditions,
                    'actions' => $rule->actions,
                    'event' => $event->normalized_payload,
                ],
            ]);

            try {
                if (!$this->conditionsMatch((array) $rule->conditions, (array) $event->normalized_payload)) {
                    $execution->status = 'ignored';
                } else {
                    foreach ((array) $rule->actions as $action) {
                        $this->executeAction((array) $action, $event);
                    }
                    $execution->status = 'processed';
                }

                $execution->finished_at = now();
                $execution->save();
            } catch (\Throwable $e) {
                $execution->status = 'failed';
                $execution->error = $e->getMessage();
                $execution->finished_at = now();
                $execution->save();
                throw $e;
            }

            if ($rule->stop_on_success && $execution->status === 'processed') {
                break;
            }
        }
    }

    private function conditionsMatch(array $conditions, array $context): bool
    {
        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            $field = (string) ($condition['field'] ?? '');
            $operator = strtolower((string) ($condition['operator'] ?? 'equals'));
            $expected = $condition['value'] ?? null;
            $actual = data_get($context, $field);

            $matches = match ($operator) {
                'equals', '=' => (string) $actual === (string) $expected,
                'not_equals', '!=' => (string) $actual !== (string) $expected,
                'contains' => str_contains(mb_strtolower((string) $actual), mb_strtolower((string) $expected)),
                'starts_with' => str_starts_with(mb_strtolower((string) $actual), mb_strtolower((string) $expected)),
                'empty' => empty($actual),
                'not_empty' => !empty($actual),
                default => false,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    private function executeAction(array $action, ConnectApiWebhookEvent $event): void
    {
        $type = (string) ($action['type'] ?? '');
        $context = (array) $event->normalized_payload;
        $to = $this->resolveValue($action['to'] ?? '{from}', $context);

        if ($type === 'connect_api.send_text') {
            $message = $this->resolveValue($action['message'] ?? '', $context);
            app(ConnectApiMessageService::class)->sendText(
                (int) $event->empresa_id,
                (string) $to,
                (string) $message
            );
            return;
        }

        if ($type === 'connect_api.send_template') {
            $eventKey = (string) ($action['event_key'] ?? '');
            $parameters = array_map(
                fn ($value) => $this->resolveValue($value, $context),
                (array) ($action['parameters'] ?? [])
            );

            app(ConnectApiMessageService::class)->sendTemplate(
                (int) $event->empresa_id,
                (string) $to,
                $eventKey,
                $parameters
            );
            return;
        }

        if ($type !== '') {
            Log::notice('Ação de automação ainda não implementada.', [
                'type' => $type,
                'event_id' => $event->id,
            ]);
        }
    }

    private function resolveValue($value, array $context)
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_replace_callback('/\{([a-zA-Z0-9_.-]+)\}/', function ($matches) use ($context) {
            return (string) data_get($context, $matches[1], '');
        }, $value);
    }
}
