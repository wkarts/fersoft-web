<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ConnectApiWebhookPayloadSanitizer;
use PHPUnit\Framework\TestCase;

class ConnectApiWebhookPayloadSanitizerTest extends TestCase
{
    public function testRemovesPairingQrSecretsAndImageDataBeforePersistence(): void
    {
        $payload = [
            'event' => 'qrcode-updated',
            'code' => 'ordinary-business-code',
            'data' => [
                'qrcode' => [
                    'code' => 'temporary-pairing-token',
                    'status' => 'pending',
                ],
                'avatar' => 'data:image/png;base64,' . str_repeat('A', 128),
            ],
        ];

        $sanitized = ConnectApiWebhookPayloadSanitizer::sanitize($payload);

        self::assertSame('temporary-pairing-token', $payload['data']['qrcode']['code']);
        self::assertSame('[CONTEUDO_TRANSITORIO_REMOVIDO]', $sanitized['data']['qrcode']['code']);
        self::assertSame('[CONTEUDO_TRANSITORIO_REMOVIDO]', $sanitized['data']['avatar']);
        self::assertSame('pending', $sanitized['data']['qrcode']['status']);
        self::assertSame('ordinary-business-code', $sanitized['code']);
    }

    public function testSanitizesQrCodeInsideSerializedJsonButKeepsOtherValues(): void
    {
        $payload = [
            'snapshot' => json_encode([
                'qrcode' => ['code' => 'serialized-pairing-token'],
                'status' => 'connected',
            ], JSON_UNESCAPED_SLASHES),
        ];

        $sanitized = ConnectApiWebhookPayloadSanitizer::sanitize($payload);
        $decoded = json_decode($sanitized['snapshot'], true);

        self::assertSame('[CONTEUDO_TRANSITORIO_REMOVIDO]', $decoded['qrcode']['code']);
        self::assertSame('connected', $decoded['status']);
    }
}
