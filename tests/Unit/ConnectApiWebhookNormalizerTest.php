<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ConnectApi\ConnectApiWebhookNormalizer;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ConnectApiWebhookNormalizerTest extends TestCase
{
    #[DataProvider('directionValues')]
    public function testPreservesDirectionWithoutPhpStringTruthiness(mixed $value, bool $expected): void
    {
        $normalizer = new ConnectApiWebhookNormalizer();
        $normalized = $normalizer->normalize([
            'event' => 'messages.upsert',
            'data' => ['key' => ['fromMe' => $value]],
        ]);

        $this->assertSame($expected, $normalized['from_me']);
        $this->assertSame($expected, ConnectApiWebhookNormalizer::isFromMe(['from_me' => $value]));
    }

    public static function directionValues(): array
    {
        return [
            'boolean true' => [true, true],
            'integer one' => [1, true],
            'string true' => ['true', true],
            'string one' => ['1', true],
            'case and whitespace' => [' TRUE ', true],
            'boolean false' => [false, false],
            'integer zero' => [0, false],
            'string false' => ['false', false],
            'string zero' => ['0', false],
            'false with whitespace' => [' FALSE ', false],
            'null' => [null, false],
            'empty string' => ['', false],
            'unknown string' => ['not-a-boolean', false],
            'unknown number' => [2, false],
            'array' => [[true], false],
            'object' => [(object) ['value' => true], false],
        ];
    }

    public function testMissingDirectionKeepsLegacyInboundBehavior(): void
    {
        $normalized = (new ConnectApiWebhookNormalizer())->normalize([]);

        $this->assertFalse($normalized['from_me']);
        $this->assertFalse(ConnectApiWebhookNormalizer::isFromMe([]));
    }

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
