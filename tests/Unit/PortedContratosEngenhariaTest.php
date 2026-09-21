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
            $root . '/database/migrations/2026_09_21_020020_create_contrato_eng_funcionarios_table.php'
        );
        $controller = file_get_contents($root . '/app/Http/Controllers/ContratoEngenhariaController.php');

        $this->assertStringContainsString("Schema::create('contrato_eng_funcionarios'", $migration);
        $this->assertStringContainsString("DB::table('contrato_eng_funcionarios'", $controller);
    }

    public function testPayablesCanBeLinkedToContractWithoutChangingExistingWorkflow(): void
    {
        $root = dirname(__DIR__, 2);
        $model = file_get_contents($root . '/app/Models/ContaPagar.php');
        $controller = file_get_contents($root . '/app/Http/Controllers/ContasPagarController.php');
        $view = file_get_contents($root . '/resources/views/contaPagar/register.blade.php');

        $this->assertStringContainsString("'contrato_eng_id'", $model);
        $this->assertStringContainsString('contratoEngenharia()', $model);
        $this->assertStringContainsString("$data['contrato_eng_id']", $controller);
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

    public function testSchemaIsSplitByResponsibility(): void
    {
        $root = dirname(__DIR__, 2);

        $createMigrations = [
            '2026_09_21_020000_create_contratos_engenharia_table.php',
            '2026_09_21_020010_create_contrato_eng_itens_table.php',
            '2026_09_21_020020_create_contrato_eng_funcionarios_table.php',
            '2026_09_21_020030_create_faturas_engenharia_table.php',
            '2026_09_21_020040_create_fatura_eng_itens_table.php',
            '2026_09_21_020050_create_fatura_eng_funcionarios_table.php',
        ];

        foreach ($createMigrations as $file) {
            $source = file_get_contents($root . '/database/migrations/' . $file);
            $this->assertIsString($source);
            $this->assertSame(1, substr_count($source, 'Schema::create('), $file);
        }

        $payable = file_get_contents(
            $root . '/database/migrations/2026_09_21_020060_add_contrato_eng_id_to_conta_pagars_table.php'
        );

        $this->assertStringContainsString("Schema::table('conta_pagars'", $payable);
        $this->assertStringNotContainsString('Schema::create(', $payable);
    }
}
