<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessConnectApiWebhookEvent;
use App\Models\ConnectApiWebhookEvent;
use App\Services\ConnectApi\ConnectApiIntegrationResolver;
use App\Services\ConnectApi\ConnectApiWebhookNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConnectApiWebhookController extends Controller
{
    public function receive(
        Request $request,
        ConnectApiIntegrationResolver $resolver,
        ConnectApiWebhookNormalizer $normalizer
    ) {
        if (!$this->validSecret($request)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $request->all();
        $normalized = $normalizer->normalize($payload);
        $instanceName = (string) ($normalized['instance_name'] ?? '');

        if ($instanceName === '') {
            return response()->json(['status' => 'ignored', 'reason' => 'instance_missing'], 202);
        }

        $instance = $resolver->byInstanceName($instanceName);

        if (!$instance) {
            Log::notice('Webhook Connect|API ignorado: instância não pertence a esta instalação FERSOFT.', [
                'instance_name' => $instanceName,
            ]);

            return response()->json(['status' => 'ignored', 'reason' => 'foreign_instance'], 202);
        }

        $deduplicationKey = hash('sha256', implode('|', [
            $instance->id,
            $normalized['event'] ?? '',
            $normalized['message_id'] ?? '',
            data_get($payload, 'date_time') ?? data_get($payload, 'data.messageTimestamp') ?? '',
            json_encode($payload),
        ]));

        $event = ConnectApiWebhookEvent::firstOrCreate(
            ['deduplication_key' => $deduplicationKey],
            [
                'connect_api_instance_id' => $instance->id,
                'empresa_id' => $instance->empresa_id,
                'event_type' => $normalized['event'] ?? 'unknown',
                'external_event_id' => (string) (
                    $payload['id']
                    ?? data_get($payload, 'data.id')
                    ?? ''
                ),
                'message_id' => $normalized['message_id'] ?: null,
                'payload' => $payload,
                'normalized_payload' => $normalized,
                'status' => 'received',
                'attempts' => 0,
                'received_at' => now(),
            ]
        );

        $instance->last_event_at = now();
        $instance->save();

        if ($event->wasRecentlyCreated) {
            ProcessConnectApiWebhookEvent::dispatch($event->id);
        }

        return response()->json([
            'status' => $event->wasRecentlyCreated ? 'accepted' : 'duplicate',
            'event_id' => $event->id,
        ], $event->wasRecentlyCreated ? 202 : 200);
    }

    private function validSecret(Request $request): bool
    {
        $expected = (string) config('connect_api.webhook_secret');

        if ($expected === '') {
            return true;
        }

        $provided = (string) (
            $request->header('X-Connect-Webhook-Secret')
            ?: $request->header('X-Webhook-Secret')
            ?: $request->bearerToken()
        );

        return $provided !== '' && hash_equals($expected, $provided);
    }
}
