<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\BinaryPayloadSanitizer;
use PHPUnit\Framework\TestCase;

class BinaryPayloadSanitizerTest extends TestCase
{
    public function testRedactsCredentialKeysWithoutChangingOrdinaryValues(): void
    {
        $sanitized = BinaryPayloadSanitizer::sanitize([
            'senha' => 'stored-secret',
            'apiKey' => 'api-secret',
            'business_code' => 'PED-42',
        ]);

        self::assertSame('[DADO_SENSIVEL_REMOVIDO]', $sanitized['senha']);
        self::assertSame('[DADO_SENSIVEL_REMOVIDO]', $sanitized['apiKey']);
        self::assertSame('PED-42', $sanitized['business_code']);
    }

    public function testRedactsCredentialsInsideJsonSnapshots(): void
    {
        $sanitized = BinaryPayloadSanitizer::sanitizeJsonString(
            '{"config":{"password":"snapshot-secret","active":true}}'
        );
        $decoded = json_decode($sanitized, true);

        self::assertSame('[DADO_SENSIVEL_REMOVIDO]', $decoded['config']['password']);
        self::assertTrue($decoded['config']['active']);
    }
}
