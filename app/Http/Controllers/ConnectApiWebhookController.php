<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessConnectApiWebhookEvent;
use App\Models\ConnectApiInstance;
use App\Models\ConnectApiWebhookEvent;
use App\Services\ConnectApi\ConnectApiWebhookNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConnectApiWebhookController extends Controller
{
    public function receive(
        Request $request,
        string $token,
        ConnectApiWebhookNormalizer $normalizer
    ) {
        $token = trim($token);

        if ($token === '' || strlen($token) < 32) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $instance = ConnectApiInstance::query()
            ->where('webhook_token_hash', hash('sha256', $token))
            ->whereNull('deleted_at')
            ->first();

        if (!$instance) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $request->all();
        $normalized = $normalizer->normalize($payload);
        $payloadInstanceName = (string) ($normalized['instance_name'] ?? '');

        if ($payloadInstanceName !== '' && !hash_equals($instance->instance_name, $payloadInstanceName)) {
            Log::warning('Webhook Connect|API rejeitado por divergência de instância.', [
                'instance_id' => $instance->id,
                'empresa_id' => $instance->empresa_id,
                'payload_instance_name' => $payloadInstanceName,
            ]);

            return response()->json([
                'status' => 'unauthorized',
                'reason' => 'instance_mismatch',
            ], 403);
        }

        // O token da URL é a autoridade. O payload externo não define empresa_id.
        $normalized['instance_name'] = $instance->instance_name;

        if (($normalized['event'] ?? '') === 'connection-update') {
            $state = (string) (
                data_get($payload, 'data.state')
                ?? data_get($payload, 'data.instance.state')
                ?? data_get($payload, 'state')
                ?? 'unknown'
            );

            $connected = (string) (
                data_get($payload, 'data.wuid')
                ?? data_get($payload, 'data.ownerJid')
                ?? data_get($payload, 'data.instance.owner')
                ?? ''
            );

            $instance->connection_status = $state;
            $instance->last_status_at = now();

            if ($connected !== '') {
                $instance->connected_number = preg_replace('/\D+/', '', explode('@', $connected)[0] ?? '');
            }

            if ($state === 'open') {
                $instance->connected_at = $instance->connected_at ?: now();
                $instance->paired_at = $instance->paired_at ?: now();
                $instance->disconnected_at = null;
            } elseif ($state === 'close') {
                $instance->disconnected_at = now();
            }
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
        $instance->webhook_last_received_at = now();
        $instance->save();

        if ($event->wasRecentlyCreated) {
            ProcessConnectApiWebhookEvent::dispatch($event->id);
        }

        return response()->json([
            'status' => $event->wasRecentlyCreated ? 'accepted' : 'duplicate',
            'event_id' => $event->id,
        ], $event->wasRecentlyCreated ? 202 : 200);
    }
}
