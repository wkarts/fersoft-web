<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ConnectApi\ConnectApiWebhookNormalizer;
use PHPUnit\Framework\TestCase;

class ConnectApiWebhookNormalizerTest extends TestCase
{
    public function testNormalizesMessagesUpsertWithoutTrustingEmpresaId(): void
    {
        $normalizer = new ConnectApiWebhookNormalizer();

        $normalized = $normalizer->normalize([
            'event' => 'messages.upsert',
            'instance' => '12345678000199-EMPRESA_TESTE',
            'empresa_id' => 999999,
            'data' => [
                'key' => [
                    'id' => 'MSG-1',
                    'remoteJid' => '5575999999999@s.whatsapp.net',
                ],
                'pushName' => 'Wallace',
                'message' => [
                    'conversation' => 'OI',
                ],
                'messageTimestamp' => 1789876800,
            ],
        ]);

        $this->assertSame('messages-upsert', $normalized['event']);
        $this->assertSame('12345678000199-EMPRESA_TESTE', $normalized['instance_name']);
        $this->assertSame('MSG-1', $normalized['message_id']);
        $this->assertSame('5575999999999', $normalized['from']);
        $this->assertSame('OI', $normalized['text']);
        $this->assertArrayNotHasKey('empresa_id', $normalized);
    }

    public function testNormalizesLocationAndLid(): void
    {
        $normalizer = new ConnectApiWebhookNormalizer();

        $normalized = $normalizer->normalize([
            'event' => 'MESSAGES_UPSERT',
            'instanceName' => 'EMPRESA',
            'data' => [
                'key' => [
                    'remoteJid' => '123456789@lid',
                ],
                'message' => [
                    'locationMessage' => [
                        'degreesLatitude' => -12.3,
                        'degreesLongitude' => -39.1,
                    ],
                ],
            ],
        ]);

        $this->assertSame('123456789', $normalized['lid']);
        $this->assertSame(-12.3, $normalized['latitude']);
        $this->assertSame(-39.1, $normalized['longitude']);
    }
}
