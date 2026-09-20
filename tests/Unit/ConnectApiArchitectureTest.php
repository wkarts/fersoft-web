<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ConnectApiArchitectureTest extends TestCase
{
    public function testActiveWhatsappIntegrationNoLongerDependsOnRetiredTransports(): void
    {
        $root = dirname(__DIR__, 2);

        $paths = [
            'routes/web.php',
            'app/Helpers/Menu.php',
            'app/Helpers/User.php',
            'app/Utils/WhatsAppUtil.php',
            'app/Http/Controllers/PontoWhatsAppController.php',
            'app/Http/Controllers/MovimentacaoVeiculoController.php',
            'app/Console/Commands/MonitorarFrota.php',
            'app/Http/Controllers/ConfigNotaController.php',
            'resources/views/configNota/index.blade.php',
            'resources/views/default/menu_superior.blade.php',
            'resources/views/default/menu_lateral.blade.php',
        ];

        $forbidden = [
            'EvoApiService',
            'EvoApiInstance',
            'evo_api_instances',
            '/evoapi',
            'evo-instances',
            'API_WHATSAPP_ATENDIMENTO',
            'sendWithLegacy',
            'EVO_WHATSAPP_',
        ];

        foreach ($paths as $path) {
            $source = file_get_contents($root . '/' . $path);
            $this->assertIsString($source, $path);

            foreach ($forbidden as $term) {
                $this->assertStringNotContainsString(
                    $term,
                    $source,
                    "{$path} ainda contém dependência ativa de {$term}."
                );
            }
        }
    }

    public function testConnectApiRoutesAndWebhookAreRegistered(): void
    {
        $root = dirname(__DIR__, 2);
        $web = file_get_contents($root . '/routes/web.php');
        $api = file_get_contents($root . '/routes/api.php');

        $this->assertStringContainsString("['prefix' => 'connect-api']", $web);
        $this->assertStringContainsString("/webhooks/connect-api", $api);
        $this->assertStringContainsString('ConnectApiInstanceController', $web);
        $this->assertStringContainsString('ConnectApiWebhookController', $api);
    }

    public function testConnectApiInstanceControllerSatisfiesBaseControllerContract(): void
    {
        $reflection = new \ReflectionClass(\App\Http\Controllers\ConnectApiInstanceController::class);

        $this->assertFalse(
            $reflection->isAbstract(),
            'ConnectApiInstanceController não pode ser abstrato.'
        );

        foreach (['rules', 'messages'] as $method) {
            $refMethod = $reflection->getMethod($method);
            $this->assertSame(
                \App\Http\Controllers\ConnectApiInstanceController::class,
                $refMethod->getDeclaringClass()->getName(),
                "O método {$method} deve ser implementado pelo ConnectApiInstanceController."
            );
        }

        $redirect = $reflection->getProperty('redirectPage');
        $redirect->setAccessible(true);
        $controller = $reflection->newInstanceWithoutConstructor();

        $this->assertSame('/connect-api', $redirect->getValue($controller));
    }

    public function testWebhookSecurityIsPerInstanceAndNotGlobalEnv(): void
    {
        $root = dirname(__DIR__, 2);
        $env = file_get_contents($root . '/.env.example');
        $config = file_get_contents($root . '/config/connect_api.php');
        $api = file_get_contents($root . '/routes/api.php');
        $model = file_get_contents($root . '/app/Models/ConnectApiInstance.php');
        $ci = file_get_contents($root . '/.github/workflows/ci.yml');
        $bootstrap = file_get_contents($root . '/scripts/ci/bootstrap-env.sh');

        foreach ([$env, $ci, $bootstrap] as $source) {
            $this->assertStringNotContainsString('CONNECT_API_WEBHOOK_SECRET', $source);
            $this->assertStringNotContainsString('CONNECT_API_WEBHOOK_URL', $source);
        }
        $this->assertStringNotContainsString('webhook_secret', $config);
        $this->assertStringNotContainsString('webhook_url', $config);
        $this->assertStringContainsString('/webhooks/connect-api/{token}', $api);
        $this->assertStringContainsString('webhook_token_hash', $model);
        $this->assertStringContainsString('webhook_configured_at', $model);
    }

    public function testGlobalWhatsappButtonUsesEmbeddedWhiteTransparentIcon(): void
    {
        $root = dirname(__DIR__, 2);
        $source = file_get_contents($root . '/app/Helpers/User.php');

        $this->assertIsString($source);
        $this->assertStringContainsString('class="whatsapp-brand-icon"', $source);
        $this->assertStringContainsString('fill:currentColor', $source);
        $this->assertStringContainsString('background:transparent', $source);
        $this->assertStringContainsString('color:#fff', $source);
        $this->assertStringNotContainsString('<i class="fa fa-whatsapp"></i>', $source);
    }

    public function testRetiredRuntimeFilesWereRemoved(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([
            'app/Services/EvoApiService.php',
            'app/Models/EvoApiInstance.php',
            'app/Http/Controllers/EvoApiInstanceController.php',
            'config/evoapi.php',
        ] as $path) {
            $this->assertFileDoesNotExist($root . '/' . $path, $path);
        }
    }
}
