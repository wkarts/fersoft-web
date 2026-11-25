<?php

namespace Tests\Unit;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Tests\TestCase;

class RelatorioDespesasSemanalViewTest extends TestCase
{
    private function renderPlanilha(Collection $documentos): string
    {
        // Renderiza a planilha sem depender das consultas do menu e do layout do ERP.
        $finder = new FileViewFinder(app('files'), [
            __DIR__ . '/../Fixtures/relatorio-despesas/views',
            resource_path('views'),
        ]);
        $views = new Factory(app('view.engine.resolver'), $finder, new Dispatcher());

        return $views->make('grupo_categorias.relatorio_planilha', [
            'documentos' => $documentos,
            'dataInicio' => '2026-11-20',
            'dataFim' => '2026-11-27',
        ])->render();
    }

    public function test_exibe_o_total_da_nota_e_os_boletos_consolidados(): void
    {
        $nota = (object) [
            'empresa_beneficiaria' => 'Fornecedor A',
            'numero_nota' => 'NF-401',
            'classificacao' => 'Operacional',
            'descriminacao' => 'Frete',
            'pagador' => 'Matriz',
            'valor_total_nota' => 1250.75,
            'boletos' => collect([
                (object) ['data_vencimento' => '2026-11-23', 'valor_boleto' => 1000.50],
                (object) ['data_vencimento' => '2026-12-01', 'valor_boleto' => 250.25],
            ]),
        ];

        $html = $this->renderPlanilha(collect([$nota]));
        foreach (['FORNECEDOR A', 'NF-401', 'Operacional', 'Frete', 'Matriz', 'R$ 1.250,75',
            '23/11/2026', '01/12/2026', 'R$ 1.000,50', 'R$ 250,25'] as $valor) {
            $this->assertStringContainsString($valor, $html);
        }
    }

    public function test_exibe_o_periodo_quando_nao_ha_documentos(): void
    {
        $html = $this->renderPlanilha(collect());

        $this->assertStringContainsString('Nenhuma despesa encontrada', $html);
        $this->assertStringContainsString('20/11/2026', $html);
        $this->assertStringContainsString('27/11/2026', $html);
    }
}
