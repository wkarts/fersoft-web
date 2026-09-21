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

        $defaults = $reflection->getDefaultProperties();

        $this->assertSame('/connect-api', $defaults['redirectPage'] ?? null);
    }

    public function testConnectApiViewsAlwaysHaveATitleFallback(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/ConnectApiInstanceController.php');
        $layout = file_get_contents($root . '/resources/views/default/menu_lateral.blade.php');

        $this->assertStringContainsString("\$title = 'Connect|API';", $controller);
        $this->assertStringContainsString("'companies'", $controller);
        $this->assertStringContainsString("'instancesByCompany'", $controller);
        $this->assertStringContainsString(
            "{{ \$title ?? config('app.name', 'FERSOFT WEB') }}",
            $layout
        );
        $this->assertStringNotContainsString('<title>{{$title}}</title>', $layout);
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

    public function testConnectApiManagerRestoresMasterGridWithoutJqueryDependency(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root . '/resources/views/connect_api/index.blade.php');
        $controller = file_get_contents($root . '/app/Http/Controllers/ConnectApiInstanceController.php');
        $routes = file_get_contents($root . '/routes/web.php');

        $this->assertStringContainsString('@forelse($companies as $empresa)', $view);
        $this->assertStringContainsString('ID: {{ $empresa->id }}', $view);
        $this->assertStringContainsString('id="connectProvisionNumber"', $view);
        $this->assertStringContainsString('JSON.stringify({ number })', $view);
        $this->assertStringContainsString('class="connect-modal"', $view);
        $this->assertStringContainsString('js-delete', $view);
        $this->assertStringNotContainsString("$('#", $view);
        $this->assertStringNotContainsString('$.', $view);
        $this->assertStringNotContainsString('.select2(', $view);
        $this->assertStringNotContainsString('empresa_id {{', $view);

        $this->assertStringContainsString('protected bool $isSuper = false;', $controller);
        $this->assertStringContainsString('Empresa::query()', $controller);
        $this->assertStringContainsString("->keyBy('empresa_id')", $controller);
        $this->assertStringContainsString('Log::error', $controller);
        $this->assertStringContainsString("Route::delete('/instances/{id}'", $routes);
    }

    public function testLegacyEvoPermissionGrantsConnectApiAccess(): void
    {
        $this->assertTrue(\App\Models\Empresa::validaLink('/connect-api', ['/evoapi']));
        $this->assertTrue(\App\Models\Empresa::validaLink('/connect-api', ['/evo-instances']));
        $this->assertTrue(\App\Models\Empresa::validaLink('/connect-api', ['/connect-api']));
        $this->assertFalse(\App\Models\Empresa::validaLink('/connect-api', ['/clientes']));
    }

    public function testLegacyPermissionMigrationCoversProfilesCompaniesAndUsers(): void
    {
        $root = dirname(__DIR__, 2);

        $migrations = [
            'database/migrations/2026_09_20_010500_inherit_connect_api_permission_in_perfil_acessos.php',
            'database/migrations/2026_09_20_010510_inherit_connect_api_permission_in_empresas.php',
            'database/migrations/2026_09_20_010520_inherit_connect_api_permission_in_usuarios.php',
        ];

        foreach ($migrations as $migration) {
            $source = file_get_contents($root . '/' . $migration);

            $this->assertIsString($source);
            $this->assertStringContainsString("'/evoapi'", $source);
            $this->assertStringContainsString("'/connect-api'", $source);
        }
    }

    public function testTenantCanSelfProvisionOnlyItsOwnConnectApiCompany(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/ConnectApiInstanceController.php');
        $view = file_get_contents($root . '/resources/views/connect_api/index.blade.php');

        $this->assertStringContainsString('tenantHasConnectApiAccess', $controller);
        $this->assertStringContainsString('authorizeCompany($empresa)', $controller);
        $this->assertStringContainsString("'Tenant não pode gerenciar outra empresa.'", $controller);
        $this->assertStringContainsString('Provisionar meu WhatsApp', $view);
        $this->assertStringContainsString('js-reprovision', $view);
    }

    public function testDeletedConnectApiInstanceIsReusedInsteadOfDuplicated(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents(
            $root . '/app/Services/ConnectApi/ConnectApiInstanceService.php'
        );
        $migration = file_get_contents(
            $root . '/database/migrations/2026_09_20_010000_create_connect_api_instances_table.php'
        );

        $this->assertStringContainsString('->withDeleted()', $service);
        $this->assertStringContainsString(
            "if (\$instance && \$instance->trashed())",
            $service
        );
        $this->assertStringContainsString("\$instance->restore();", $service);
        $this->assertStringContainsString('resetForFreshProvisioning', $service);
        $this->assertStringContainsString(
            "\$table->unique('empresa_id', 'connect_api_instances_empresa_unique')",
            $migration
        );
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
    public function testWhatsappRuntimeNoLongerDependsOnLegacyManualToken(): void
    {
        $root = dirname(__DIR__, 2);
        $base = file_get_contents($root . '/app/Http/Controllers/BaseController.php');
        $pesagens = file_get_contents($root . '/resources/views/pesagens/list.blade.php');

        $this->assertStringNotContainsString('getWhatsAppConfig', $base);
        $this->assertStringNotContainsString('token_whatsapp', $base);
        $this->assertStringContainsString('getConnectApiWhatsAppState', $base);
        $this->assertStringContainsString('assertWhatsAppSendSucceeded', $base);

        $this->assertStringNotContainsString('token_whatsapp', $pesagens);
        $this->assertStringNotContainsString(
            'Necessário habilitar o token para acesso à API do WhatsApp',
            $pesagens
        );
        $this->assertStringContainsString('connectApiWhatsAppReady', $pesagens);
        $this->assertStringContainsString('data-connect-status', $pesagens);
    }

    public function testMessageServiceRequiresProvisionedAndConnectedConnectApiInstance(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents(
            $root . '/app/Services/ConnectApi/ConnectApiMessageService.php'
        );

        $this->assertStringContainsString(
            "if (!\$instance->provisioned_at || !\$instance->instance_token)",
            $service
        );
        $this->assertStringContainsString(
            "\$instance = \$this->instances->refreshStatus(\$instance);",
            $service
        );
        $this->assertStringContainsString(
            "if (\$instance->connection_status !== 'open')",
            $service
        );
    }

    public function testProvisioningRefreshesPairingResourcesWithoutManualReload(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root . '/resources/views/connect_api/index.blade.php');

        $this->assertStringContainsString(
            'Atualizando os recursos de QR Code, código de pareamento e teste',
            $view
        );
        $this->assertStringContainsString(
            'window.setTimeout(() => window.location.reload(), 700);',
            $view
        );
    }


}
