<?php

namespace Tests\Unit;

use App\Models\Pesagem;
use App\Models\TicketPesagem;
use App\Support\PesagemReportCalculator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PesagemReportCalculatorTest extends TestCase
{
    public function test_peso_final_persistido_zero_nao_vira_desconto_total_do_relatorio(): void
    {
        $pesagem = new Pesagem([
            'status' => 'em andamento',
            'tipo' => 'venda',
            'peso_final' => 0,
            'danificado' => 0,
            'quebrado' => 0,
            'esverdeado' => 0,
            'ardido' => 0,
            'secagem' => 0,
            'umidade_desconto' => 0,
            'impureza_desconto' => 0,
        ]);

        $entrada = new TicketPesagem([
            'produto_id' => 1,
            'tipo' => 'entrada',
            'peso' => 12860,
            'peso_bag' => 0,
            'inicio' => '2026-07-16 17:53:18',
        ]);
        $entrada->setRelation('produto', null);

        $saida = new TicketPesagem([
            'produto_id' => 1,
            'tipo' => 'saida',
            'peso' => 38260,
            'peso_bag' => 0,
            'inicio' => '2026-06-16 18:12:24',
        ]);
        $saida->setRelation('produto', null);

        $pesagem->setRelation('tickets', new Collection([$entrada, $saida]));

        $resumo = PesagemReportCalculator::summarize($pesagem);

        $this->assertSame(25400.0, (float) $resumo['peso_liquido_total']);
        $this->assertSame(0.0, (float) $resumo['descontos']);
        $this->assertSame(25400.0, (float) $resumo['peso_final_liquido']);
    }

    public function test_impurezas_sao_apenas_recipiente_e_percentuais_reais(): void
    {
        $pesagem = new Pesagem([
            'status' => 'concluído',
            'tipo' => 'venda',
            'peso_final' => 0,
            'danificado' => 0,
            'quebrado' => 0,
            'esverdeado' => 0,
            'ardido' => 0,
            'secagem' => 0,
            'umidade_desconto' => 1,
            'impureza_desconto' => 1,
        ]);

        $entrada = new TicketPesagem([
            'produto_id' => 1,
            'tipo' => 'entrada',
            'peso' => 14490,
            'peso_bag' => 30,
            'inicio' => '2026-06-16 10:00:00',
        ]);
        $entrada->setRelation('produto', null);

        $saida = new TicketPesagem([
            'produto_id' => 1,
            'tipo' => 'saida',
            'peso' => 39520,
            'peso_bag' => 0,
            'inicio' => '2026-06-16 13:00:00',
        ]);
        $saida->setRelation('produto', null);

        $pesagem->setRelation('tickets', new Collection([$entrada, $saida]));

        $resumo = PesagemReportCalculator::summarize($pesagem);

        // 25.030 kg de diferença física.
        $this->assertSame(25030.0, (float) $resumo['peso_liquido_total']);

        // 30 kg de recipiente + 2% de 25.030 (500,60) = 530,60 kg.
        $this->assertEqualsWithDelta(530.60, (float) $resumo['descontos'], 0.001);
        $this->assertEqualsWithDelta(24499.40, (float) $resumo['peso_final_liquido'], 0.001);
    }
}
