<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PortedMtr3Test extends TestCase
{
    public function testMtrControllersAndModelsFollowProjectBaseClasses(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([
            'app/Http/Controllers/MtrController.php',
            'app/Http/Controllers/MtrUnidadeController.php',
            'app/Http/Controllers/MtrDeparaController.php',
        ] as $path) {
            $source = file_get_contents($root . '/' . $path);

            $this->assertStringContainsString('extends BaseController', $source, $path);
            $this->assertStringContainsString('protected function rules(): array', $source, $path);
            $this->assertStringContainsString('protected function messages(): array', $source, $path);
            $this->assertStringContainsString('redirectPage', $source, $path);
        }

        foreach ([
            'app/Models/MtrConfig.php',
            'app/Models/MtrDeparaResiduo.php',
            'app/Models/MtrManifesto.php',
            'app/Models/MtrManifestoItem.php',
            'app/Models/MtrResiduo.php',
        ] as $path) {
            $source = file_get_contents($root . '/' . $path);
            $this->assertStringContainsString('extends BaseModel', $source, $path);
        }
    }

    public function testExistingReconciledMtrMigrationsAreReusedWithoutNewSchema(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([
            '2026_09_18_120013_create_mtr_configs_table.php',
            '2026_09_18_120014_create_mtr_depara_residuos_table.php',
            '2026_09_18_120015_create_mtr_manifestos_table.php',
            '2026_09_18_120016_create_mtr_residuos_table.php',
            '2026_09_18_120041_create_mtr_manifesto_itens_table.php',
        ] as $file) {
            $this->assertFileExists($root . '/database/migrations/' . $file);
        }

        $migrationDir = $root . '/database/migrations';
        $duplicates = glob($migrationDir . '/2026_09_21_*mtr*.php') ?: [];

        $this->assertSame(
            [],
            $duplicates,
            'MTR 3.0 deve consumir as migrations históricas já conciliadas, sem schema paralelo.'
        );
    }

    public function testMtrCodeRespectsHistoricalEnums(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/MtrController.php');

        $this->assertStringContainsString("'ticket_pesagem'", $controller);
        $this->assertStringNotContainsString("'tipo_origem' => 'pesagem'", $controller);
        $this->assertStringNotContainsString("'tipo_origem' => 'recebimento_externo'", $controller);
        $this->assertStringNotContainsString("'status' => 'recebido'", $controller);

        foreach (['rascunho', 'transmitido', 'cancelado', 'erro'] as $status) {
            $this->assertStringContainsString("'{$status}'", $controller);
        }
    }

    public function testMtrCredentialsAreEncryptedWithoutSchemaChange(): void
    {
        $root = dirname(__DIR__, 2);
        $model = file_get_contents($root . '/app/Models/MtrConfig.php');

        $this->assertStringContainsString('Crypt::encryptString', $model);
        $this->assertStringContainsString('Crypt::decryptString', $model);
        $this->assertStringContainsString("protected \$hidden = ['senha'];", $model);
        $this->assertStringContainsString('return $value;', $model);
    }

    public function testMtrWhatsappUsesApplicationFacadeNotConnectApiDirectly(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/MtrController.php');

        $this->assertStringContainsString('$this->whatsapputil->sendMessage(', $controller);
        $this->assertStringNotContainsString('ConnectApiClient', $controller);
        $this->assertStringNotContainsString('ConnectApiMessageService', $controller);
        $this->assertStringNotContainsString('/message/sendText', $controller);
    }

    public function testMtrRoutesAndMenuAreRegistered(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = file_get_contents($root . '/routes/web.php');
        $menu = file_get_contents($root . '/app/Helpers/Menu.php');

        foreach ([
            "['prefix' => 'mtr/unidades'",
            "['prefix' => 'mtr/depara-residuos'",
            "['prefix' => 'mtr/emissao'",
            "route('mtr.emissao",
        ] as $needle) {
            $this->assertStringContainsString($needle, $routes . file_get_contents($root . '/resources/views/mtr/emissao/index.blade.php'));
        }

        foreach ([
            "'rota' => '/mtr/emissao'",
            "'rota' => '/mtr/recepcao'",
            "'rota' => '/mtr/depara-residuos'",
            "'rota' => '/mtr/unidades'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $menu);
        }
    }
    public function testHistoricalMtrViewsKeepOriginalMechanics(): void
    {
        $root = dirname(__DIR__, 2);
        $create = file_get_contents(
            $root . '/resources/views/mtr/emissao/create.blade.php'
        );
        $edit = file_get_contents(
            $root . '/resources/views/mtr/emissao/edit.blade.php'
        );
        $index = file_get_contents(
            $root . '/resources/views/mtr/emissao/index.blade.php'
        );
        $recepcao = file_get_contents(
            $root . '/resources/views/mtr/recepcao/index.blade.php'
        );
        $routes = file_get_contents($root . '/routes/web.php');

        $this->assertStringContainsString('name="nome_motorista"', $create);
        $this->assertStringContainsString('name="origem_tipo"', $create);
        $this->assertStringContainsString('name="codigo_ibama"', $create);
        $this->assertStringContainsString('name="nome_motorista"', $edit);
        $this->assertStringNotContainsString(
            "@include('mtr.emissao.form')",
            $create
        );
        $this->assertStringNotContainsString(
            "@include('mtr.emissao.form')",
            $edit
        );

        foreach ([
            'mtr.emissao.transmitir',
            'mtr.emissao.pdf',
            'mtr.emissao.cdf',
            'mtr.emissao.create.nfe',
            'mtr.emissao.create.pesagem',
        ] as $route) {
            $this->assertStringContainsString($route, $index);
        }

        $this->assertStringContainsString('/mtr/detalhes/', $recepcao);
        $this->assertStringContainsString('/mtr/receber-completo', $recepcao);
        $this->assertStringContainsString(
            "Route::get('/detalhes/{numero}'",
            $routes
        );
        $this->assertStringContainsString(
            "Route::post('/receber-completo'",
            $routes
        );
    }

    public function testMtrWhatsappPropagatesProviderFailure(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents(
            $root . '/app/Http/Controllers/MtrController.php'
        );

        $this->assertStringContainsString(
            "$providerResponse = json_decode($resultado, true);",
            $controller
        );
        $this->assertStringContainsString(
            "!(($providerResponse['success'] ?? false))",
            str_replace(
                "!is_array($providerResponse) || !($providerResponse['success'] ?? false)",
                "!(($providerResponse['success'] ?? false))",
                $controller
            )
        );
    }


}
