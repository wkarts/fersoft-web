<?php

namespace Tests\Unit;

use App\Models\Pesagem;
use App\Models\TicketPesagem;
use App\Prints\PesagemPrint80Simples;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class PesagemPrint80SimplesTest extends TestCase
{
    private function calcular(Pesagem $pesagem): array
    {
        $method = new ReflectionMethod(PesagemPrint80Simples::class, 'calcularResumoSimplesHistorico');
        $method->setAccessible(true);

        return $method->invoke(null, $pesagem);
    }

    public function test_peso_final_persistido_zero_nao_vira_impureza_no_ticket_simples(): void
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
            'tipo' => 'entrada',
            'peso' => 12860,
            'peso_bag' => 0,
        ]);

        $saida = new TicketPesagem([
            'tipo' => 'saida',
            'peso' => 38260,
            'peso_bag' => 0,
        ]);

        $pesagem->setRelation('tickets', new Collection([$entrada, $saida]));

        $resumo = $this->calcular($pesagem);

        $this->assertSame(25400.0, (float) $resumo['peso_liquido']);
        $this->assertSame(0.0, (float) $resumo['impurezas']);
        $this->assertSame(25400.0, (float) $resumo['peso_final']);
    }

    public function test_impurezas_do_ticket_simples_sao_recipiente_e_percentuais_reais(): void
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
            'tipo' => 'entrada',
            'peso' => 14490,
            'peso_bag' => 30,
        ]);

        $saida = new TicketPesagem([
            'tipo' => 'saida',
            'peso' => 39520,
            'peso_bag' => 0,
        ]);

        $pesagem->setRelation('tickets', new Collection([$entrada, $saida]));

        $resumo = $this->calcular($pesagem);

        $this->assertSame(25030.0, (float) $resumo['peso_liquido']);
        $this->assertEqualsWithDelta(530.60, (float) $resumo['impurezas'], 0.001);
        $this->assertEqualsWithDelta(24499.40, (float) $resumo['peso_final'], 0.001);
    }
}
