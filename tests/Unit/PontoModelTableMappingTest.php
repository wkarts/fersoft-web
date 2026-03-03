<?php

namespace Tests\Unit;

use App\Models\PontoAjusteAprovacao;
use App\Models\PontoMarcacao;
use Tests\TestCase;

class PontoModelTableMappingTest extends TestCase
{
    public function test_ponto_marcacao_usa_tabela_correta(): void
    {
        $this->assertSame('ponto_marcacoes', (new PontoMarcacao())->getTable());
    }

    public function test_ponto_ajuste_aprovacao_usa_tabela_correta(): void
    {
        $this->assertSame('ponto_ajuste_aprovacoes', (new PontoAjusteAprovacao())->getTable());
    }
}
