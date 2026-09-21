<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PortedContratosEngenhariaTest extends TestCase
{
    public function testEngineeringContractRoutesAndMenuAreRegistered(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = file_get_contents($root . '/routes/web.php');
        $menu = file_get_contents($root . '/app/Helpers/Menu.php');

        $this->assertStringContainsString("['prefix' => 'contratos']", $routes);
        $this->assertStringContainsString("['prefix' => 'contratos/medicoes'", $routes);
        $this->assertStringContainsString("'rota' => '/contratos'", $menu);
        $this->assertStringContainsString("'rota' => '/contratos/medicoes'", $menu);
        $this->assertStringContainsString("'rota' => '/contratos/dashboard-dre'", $menu);
    }

    public function testEngineeringModelsUseCorrectedForeignKeys(): void
    {
        $root = dirname(__DIR__, 2);
        $item = file_get_contents($root . '/app/Models/FaturaEngItem.php');
        $fatura = file_get_contents($root . '/app/Models/FaturaEngenharia.php');

        $this->assertStringContainsString("'fatura_eng_id'", $item);
        $this->assertStringContainsString("belongsTo(FaturaEngenharia::class, 'fatura_eng_id')", $item);
        $this->assertStringContainsString("'municipio_prestacao_id'", $fatura);
        $this->assertStringContainsString("belongsTo(Cidade::class, 'municipio_prestacao_id')", $fatura);
    }

    public function testContractEmployeeTableExistsBecauseControllerUsesIt(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = file_get_contents(
            $root . '/database/migrations/2026_09_18_120036_create_contrato_eng_funcionarios_table.php'
        );
        $controller = file_get_contents($root . '/app/Http/Controllers/ContratoEngenhariaController.php');

        $this->assertStringContainsString("Schema::create('contrato_eng_funcionarios'", $migration);
        $this->assertStringContainsString('ContratoEngFuncionario::create', $controller);
    }

    public function testPayablesCanBeLinkedToContractWithoutChangingExistingWorkflow(): void
    {
        $root = dirname(__DIR__, 2);
        $model = file_get_contents($root . '/app/Models/ContaPagar.php');
        $controller = file_get_contents($root . '/app/Http/Controllers/ContasPagarController.php');
        $view = file_get_contents($root . '/resources/views/contaPagar/register.blade.php');

        $this->assertStringContainsString("'contrato_eng_id'", $model);
        $this->assertStringContainsString('contratoEngenharia()', $model);
        $this->assertStringContainsString("\$data['contrato_eng_id']", $controller);
        $this->assertStringContainsString('name="contrato_eng_id"', $view);

        // Recursos preexistentes de Contas a Pagar devem continuar presentes.
        $this->assertStringContainsString('name="fornecedor_id"', $view);
        $this->assertStringContainsString('name="categoria_id"', $view);
        $this->assertStringContainsString('name="tipo_pagamento"', $view);
        $this->assertStringContainsString('name="veiculo_id"', $view);
        $this->assertStringContainsString('name="conta_id"', $view);
    }

    public function testMeasurementsIntegrateFinancialAndCommunicationFlows(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/ContratoEngMedicaoController.php');

        $this->assertStringContainsString("DB::table('conta_recebers')->insert", $controller);
        $this->assertStringContainsString("where('tipo', 'receber')", $controller);
        $this->assertStringContainsString("app(\\App\\Utils\\WhatsAppUtil::class)", $controller);
        $this->assertStringContainsString('Mail::send', $controller);
        $this->assertStringContainsString('renderPdf', $controller);
    }

    public function testNewContractControllersAndModelsFollowProjectBaseClasses(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([
            'app/Http/Controllers/ContratoEngenhariaController.php',
            'app/Http/Controllers/ContratoEngMedicaoController.php',
        ] as $path) {
            $source = file_get_contents($root . '/' . $path);
            $this->assertStringContainsString('extends BaseController', $source, $path);
            $this->assertStringContainsString('protected function rules(): array', $source, $path);
            $this->assertStringContainsString('protected function messages(): array', $source, $path);
            $this->assertStringContainsString('redirectPage', $source, $path);
        }

        foreach ([
            'app/Models/ContratoEngenharia.php',
            'app/Models/ContratoEngItem.php',
            'app/Models/ContratoEngFuncionario.php',
            'app/Models/FaturaEngenharia.php',
            'app/Models/FaturaEngItem.php',
            'app/Models/FaturaEngFuncionario.php',
        ] as $path) {
            $source = file_get_contents($root . '/' . $path);
            $this->assertStringContainsString('extends BaseModel', $source, $path);
        }
    }

    public function testHistoricalReconciledMigrationsAreReusedWithoutDuplicates(): void
    {
        $root = dirname(__DIR__, 2);

        $historical = [
            '2026_09_18_120004_create_contratos_engenharia_table.php',
            '2026_09_18_120007_create_faturas_engenharia_table.php',
            '2026_09_18_120036_create_contrato_eng_funcionarios_table.php',
            '2026_09_18_120037_create_contrato_eng_itens_table.php',
            '2026_09_18_120038_create_fatura_eng_funcionarios_table.php',
            '2026_09_18_120039_create_fatura_eng_itens_table.php',
            '2026_09_18_120103_update_conta_pagars_table.php',
        ];

        foreach ($historical as $file) {
            $this->assertFileExists($root . '/database/migrations/' . $file);
        }

        $duplicates = [
            '2026_09_21_020000_create_contratos_engenharia_table.php',
            '2026_09_21_020010_create_contrato_eng_itens_table.php',
            '2026_09_21_020020_create_contrato_eng_funcionarios_table.php',
            '2026_09_21_020030_create_faturas_engenharia_table.php',
            '2026_09_21_020040_create_fatura_eng_itens_table.php',
            '2026_09_21_020050_create_fatura_eng_funcionarios_table.php',
            '2026_09_21_020060_add_contrato_eng_id_to_conta_pagars_table.php',
        ];

        foreach ($duplicates as $file) {
            $this->assertFileDoesNotExist(
                $root . '/database/migrations/' . $file,
                'Não recriar schema já conciliado pela migration histórica: ' . $file
            );
        }

        $payable = file_get_contents(
            $root . '/database/migrations/2026_09_18_120103_update_conta_pagars_table.php'
        );
        $this->assertStringContainsString('contrato_eng_id', $payable);
    }

    public function testPortedCodeUsesHistoricalColumnsAndBaseModelTenantInjection(): void
    {
        $root = dirname(__DIR__, 2);
        $itemModel = file_get_contents($root . '/app/Models/FaturaEngItem.php');
        $employeeModel = file_get_contents($root . '/app/Models/FaturaEngFuncionario.php');
        $contractItemModel = file_get_contents($root . '/app/Models/ContratoEngItem.php');
        $controller = file_get_contents($root . '/app/Http/Controllers/ContratoEngMedicaoController.php');

        $this->assertStringNotContainsString("'sub_total'", $itemModel);
        $this->assertStringNotContainsString("'descricao'", $itemModel);
        $this->assertStringNotContainsString("['sub_total']", $controller);

        foreach ([$itemModel, $employeeModel, $contractItemModel] as $model) {
            $this->assertStringContainsString('extends BaseModel', $model);
            $this->assertStringContainsString("'empresa_id'", $model);
            $this->assertStringContainsString("'filial_id'", $model);
            $this->assertStringContainsString("'usuario_id'", $model);
        }
    }
    public function testHistoricalMeasurementViewsAndMechanicsRemainCompatible(): void
    {
        $root = dirname(__DIR__, 2);
        $index = file_get_contents(
            $root . '/resources/views/contratos/medicoes/index.blade.php'
        );
        $create = file_get_contents(
            $root . '/resources/views/contratos/medicoes/create.blade.php'
        );
        $edit = file_get_contents(
            $root . '/resources/views/contratos/medicoes/edit.blade.php'
        );
        $controller = file_get_contents(
            $root . '/app/Http/Controllers/ContratoEngMedicaoController.php'
        );
        $routes = file_get_contents($root . '/routes/web.php');

        foreach ([
            'Emitir NFS-e',
            'Visualizar Danfe',
            'Imprimir',
            'Baixar XML',
            'Consultar NFS-e',
            'Enviar Email',
            'Cancelar',
            'WhatsApp',
        ] as $label) {
            $this->assertStringContainsString($label, $index);
        }

        $this->assertStringContainsString(
            "url('contratos/medicoes/nfse/emitir')",
            $index
        );
        $this->assertStringContainsString(
            "url('contratos/medicoes/nfse/imprimir')",
            $index
        );
        $this->assertStringContainsString(
            "url('contratos/medicoes/nfse/xml')",
            $index
        );
        $this->assertStringContainsString(
            "url('contratos/medicoes/nfse/consultar')",
            $index
        );
        $this->assertStringNotContainsString("url('nfse/emitir')", $index);
        $this->assertStringNotContainsString("url('nfse/imprimir')", $index);

        $this->assertStringContainsString(
            'name="municipio_prestacao_id"',
            $create
        );
        $this->assertStringContainsString(
            'name="cidade_prestacao_id"',
            $edit
        );
        $this->assertStringContainsString(
            'private function municipioPrestacaoId',
            $controller
        );
        $this->assertStringContainsString(
            "Route::get('/cancelar/{id}', 'ContratoEngMedicaoController@cancelar')",
            $routes
        );
    }

    public function testMeasurementNfseBridgeUsesFiscalSequenceAndExternalCredentials(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents(
            $root . '/app/Http/Controllers/NfseNacionalController.php'
        );
        $services = file_get_contents($root . '/config/services.php');
        $routes = file_get_contents($root . '/routes/web.php');

        $this->assertStringContainsString('extends BaseController', $controller);
        $this->assertStringContainsString('ultimo_numero_nfse', $controller);
        $this->assertStringContainsString('numero_serie_nfse', $controller);
        $this->assertStringContainsString(
            "config('services.nfse_saatri.username')",
            $controller
        );
        $this->assertStringContainsString(
            "config('services.nfse_saatri.password')",
            $controller
        );
        $this->assertStringContainsString("'nfse_saatri'", $services);

        foreach (['emitir', 'consultar', 'imprimir', 'xml'] as $action) {
            $this->assertStringContainsString(
                "/nfse/{$action}/",
                $routes
            );
        }
    }


}
