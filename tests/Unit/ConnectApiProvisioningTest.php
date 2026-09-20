<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ConnectApi\ConnectApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConnectApiProvisioningTest extends TestCase
{
    public function testCreateInstanceSendsTechnicalIntegrationAndProvisioningPhone(): void
    {
        config([
            'connect_api.base_url' => 'https://connect.test',
            'connect_api.bootstrap_key' => 'bootstrap-key',
            'connect_api.timeout' => 30,
            'connect_api.connect_timeout' => 10,
        ]);

        Http::fake([
            'https://connect.test/instance/create' => Http::response([
                'instance' => [
                    'instanceName' => 'FERSOFT-TESTE',
                    'instanceId' => 'uuid',
                    'integration' => 'WHATSAPP-BAILEYS',
                    'status' => 'connecting',
                ],
                'hash' => 'instance-token',
                'qrcode' => [
                    'pairingCode' => '1234-5678',
                ],
            ], 201),
        ]);

        $client = new ConnectApiClient();
        $response = $client->createInstance(
            'FERSOFT-TESTE',
            'instance-token',
            '(55) 75 99999-9999'
        );

        $this->assertTrue($response['success']);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://connect.test/instance/create'
                && $request->hasHeader('apikey', 'bootstrap-key')
                && $request['instanceName'] === 'FERSOFT-TESTE'
                && $request['integration'] === 'WHATSAPP-BAILEYS'
                && $request['number'] === '5575999999999'
                && $request['qrcode'] === true;
        });
    }

    public function testWebhookPayloadUsesCurrentConnectApiFieldNames(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Services/ConnectApi/ConnectApiClient.php'
        );

        $this->assertStringContainsString("'byEvents' => false", $source);
        $this->assertStringContainsString("'base64' => false", $source);
        $this->assertStringNotContainsString("'webhookByEvents' => false", $source);
        $this->assertStringNotContainsString("'webhookBase64' => false", $source);
    }

    public function testRemoteValidationMessageIsPreferredOverGenericBadRequest(): void
    {
        config([
            'connect_api.base_url' => 'https://connect.test',
            'connect_api.bootstrap_key' => 'bootstrap-key',
        ]);

        Http::fake([
            'https://connect.test/instance/create' => Http::response([
                'error' => 'Bad Request',
                'response' => [
                    'message' => 'Invalid integration',
                ],
            ], 400),
        ]);

        $client = new ConnectApiClient();
        $response = $client->createInstance(
            'FERSOFT-TESTE',
            'instance-token',
            '5575999999999'
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Invalid integration', $response['error']);
    }
}
