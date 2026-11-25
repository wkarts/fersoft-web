<?php

namespace App\Services\ConnectApi;

class ConnectApiWebhookNormalizer
{
    public function normalize(array $payload): array
    {
        $event = strtolower((string) (
            $payload['event']
            ?? $payload['type']
            ?? data_get($payload, 'data.event')
            ?? 'unknown'
        ));

        $instanceName = (string) (
            $payload['instance']
            ?? $payload['instanceName']
            ?? data_get($payload, 'data.instance')
            ?? data_get($payload, 'data.instanceName')
            ?? ''
        );

        $remoteJid = (string) (
            data_get($payload, 'data.key.remoteJid')
            ?? data_get($payload, 'data.remoteJid')
            ?? ''
        );

        $messageId = (string) (
            data_get($payload, 'data.key.id')
            ?? data_get($payload, 'data.messageId')
            ?? $payload['messageId']
            ?? ''
        );

        $text = (string) (
            data_get($payload, 'data.message.conversation')
            ?? data_get($payload, 'data.message.extendedTextMessage.text')
            ?? data_get($payload, 'data.message.imageMessage.caption')
            ?? data_get($payload, 'data.message.videoMessage.caption')
            ?? $payload['message']
            ?? $payload['body']
            ?? ''
        );

        $location = data_get($payload, 'data.message.locationMessage');

        return [
            'event' => str_replace(['_', '.'], '-', $event),
            'instance_name' => $instanceName,
            'message_id' => $messageId,
            'remote_jid' => $remoteJid,
            'from' => preg_replace('/\D+/', '', explode('@', $remoteJid)[0] ?? ''),
            'lid' => str_contains($remoteJid, '@lid') ? explode('@', $remoteJid)[0] : null,
            'push_name' => data_get($payload, 'data.pushName'),
            'text' => trim($text),
            'latitude' => is_array($location) ? ($location['degreesLatitude'] ?? null) : null,
            'longitude' => is_array($location) ? ($location['degreesLongitude'] ?? null) : null,
            'timestamp' => data_get($payload, 'data.messageTimestamp') ?? now()->timestamp,
        ];
    }
}
