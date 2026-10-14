<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class VendasExclusaoLogicaRegressaoTest extends TestCase
{
    private function codigo(string $relativo): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $relativo);
    }

    public function test_models_filtram_sem_afetar_numeracao_fiscal_ou_base_model(): void
    {
        $vendas = $this->codigo('app/Models/Venda.php');
        $pdv = $this->codigo('app/Models/VendaCaixa.php');
        $base = $this->codigo('app/Models/BaseModel.php');

        $this->assertSame(8, substr_count($vendas, 'Venda::withoutDeleted()'));
        $this->assertSame(8, substr_count($pdv, 'VendaCaixa::withoutDeleted()'));
        $this->assertStringContainsString('public static function lastNF(', $vendas);
        $this->assertStringContainsString('public static function lastNFCe(', $pdv);
        $this->assertStringContainsString('function scopeWithoutDeleted($query)', $base);
        $this->assertStringContainsString('function scopeWithDeleted($query)', $base);
        $this->assertStringContainsString('function forceDeleteSmart()', $base);
        $this->assertStringNotContainsString('use SoftDeletes;', $pdv);
    }

    public function test_exclusoes_web_impedem_repeticao_e_protegem_estoque(): void
    {
        $vendas = $this->codigo('app/Http/Controllers/VendaController.php');
        $pdv = $this->codigo('app/Http/Controllers/FrontBoxController.php');
        $balcao = $this->codigo('app/Http/Controllers/VendaBalcaoController.php');

        foreach ([$vendas, $pdv, $balcao] as $controller) {
            $this->assertStringContainsString('DB::transaction(', $controller);
            $this->assertStringContainsString('->lockForUpdate()', $controller);
            $this->assertStringContainsString('->trashed()', $controller);
        }

        $this->assertStringContainsString('if(!$venda->retorno_estoque)', $pdv);
        $this->assertStringContainsString('if($venda->retorno_estoque)', $pdv);
        $this->assertStringContainsString("'ja_estornado'", $pdv);
        $this->assertStringContainsString('VendaBalcao::withoutDeleted()', $balcao);
        $this->assertStringNotContainsString('::withoutDeleted()->query()', $vendas);
    }

    public function test_grids_e_apis_nao_retornam_vendas_excluidas(): void
    {
        $vendas = $this->codigo('app/Http/Controllers/VendaController.php');
        $pdv = $this->codigo('app/Http/Controllers/FrontBoxController.php');
        $apiNfe = $this->codigo('app/Http/Controllers/AppFiscal/VendaController.php');
        $apiNfce = $this->codigo('app/Http/Controllers/AppFiscal/VendaCaixaController.php');
        $pdva = $this->codigo('app/Http/Controllers/PDVA/VendaController.php');

        $this->assertGreaterThanOrEqual(2, substr_count($vendas, 'Venda::withoutDeleted()'));
        $this->assertGreaterThanOrEqual(4, substr_count($pdv, 'withoutDeleted()'));
        $this->assertStringNotContainsString("VendaCaixa::whereIn('id', \$data)->get()", $pdv);

        foreach ([$apiNfe, $apiNfce, $pdva] as $api) {
            $this->assertStringContainsString('::withoutDeleted()', $api);
        }

        $this->assertStringContainsString('->findOrFail($request->id)', $apiNfe);
        $this->assertStringContainsString('->findOrFail($request->id)', $apiNfce);
        $this->assertStringContainsString('$delete || $venda->trashed()', $apiNfe);
        $this->assertStringContainsString('$delete || $venda->trashed()', $apiNfce);
    }

    public function test_relatorios_comerciais_ignoram_excluidas_sem_apagar_auditoria_fiscal(): void
    {
        $report = $this->codigo('app/Http/Controllers/RelatorioController.php');
        $super = $this->codigo('app/Http/Controllers/RelatorioSuperController.php');

        $this->assertGreaterThanOrEqual(30, substr_count($report, 'withoutDeleted()'));
        $this->assertGreaterThanOrEqual(4, substr_count($report, "Schema::hasColumn('vendas', 'deleted_at')"));
        $this->assertGreaterThanOrEqual(4, substr_count($report, "Schema::hasColumn('venda_caixas', 'deleted_at')"));
        $this->assertSame(4, substr_count($super, 'withoutDeleted()'));
        $this->assertStringNotContainsString('::withoutDeleted()->query()', $report);

        // O relatório fiscal histórico mantém notas canceladas/autorizadas
        // conforme seus estados próprios; exclusão comercial não apaga prova fiscal.
        $start = strpos($report, 'public function fiscal(Request $request)');
        $end = strpos($report, 'public function porCfop(Request $request)', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $fiscal = substr($report, $start, $end - $start);
        $this->assertStringNotContainsString('withoutDeleted()', $fiscal);
    }
}
