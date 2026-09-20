<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ConnectApi\ConnectApiClient;
use App\Services\ConnectApi\ConnectApiInstanceService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ConnectApiWebhookUrlTest extends TestCase
{
    public function testWebhookBaseUrlUsesCurrentPublicRequestHost(): void
    {
        $this->app->instance('request', Request::create(
            'https://cliente.fersofterp.com.br/connect-api',
            'POST'
        ));

        $service = new ConnectApiInstanceService(
            $this->createMock(ConnectApiClient::class)
        );

        $this->assertSame(
            'https://cliente.fersofterp.com.br',
            $service->detectPublicBaseUrl()
        );

        $this->assertSame(
            'https://cliente.fersofterp.com.br/api/webhooks/connect-api/TOKEN12345678901234567890123456789012',
            $service->buildWebhookUrl('TOKEN12345678901234567890123456789012')
        );
    }

    public function testWebhookBaseUrlFallsBackToAppUrlWithoutHttpRequest(): void
    {
        $this->app->forgetInstance('request');
        config(['app.url' => 'https://fallback.fersofterp.com.br/']);

        $service = new ConnectApiInstanceService(
            $this->createMock(ConnectApiClient::class)
        );

        $this->assertSame(
            'https://fallback.fersofterp.com.br',
            $service->detectPublicBaseUrl()
        );
    }
}
