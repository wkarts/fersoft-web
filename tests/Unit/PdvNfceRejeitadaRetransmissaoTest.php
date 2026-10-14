<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PdvNfceRejeitadaRetransmissaoTest extends TestCase
{
    public function test_botoes_da_grid_e_dos_cards_permitirem_rejeitada_mesmo_com_numero(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root . '/resources/views/frontBox/list.blade.php');
        $controller = file_get_contents($root . '/app/Http/Controllers/NFCeController.php');

        // Mesma condição nas duas apresentações (tabela e cards responsivos).
        $condicao = "@if((\$v->estado == 'REJEITADO' || (!\$v->NFcNumero && \$v->estado == 'DISPONIVEL')) && !\$v->rascunho && !\$v->consignado)";
        $this->assertSame(2, substr_count($view, $condicao));
        $this->assertStringContainsString("Retransmitir NFCe", $view);

        // O backend já aceita rejeição e transmite usando a rota original.
        $this->assertStringContainsString("\$venda->estado == 'REJEITADO' || \$venda->estado == 'DISPONIVEL'", $controller);
        $this->assertStringContainsString('emitirNFCe({{$v->id}})', $view);
        $this->assertStringContainsString('btn_envia_grid_', $view);
    }

    public function test_estado_autorizado_e_rascunho_nao_habilitam_retransmissao(): void
    {
        // Valida a matriz da regra Blade sem passar por autorização fiscal.
        $podeEnviar = static fn ($estado, $numero, $rascunho = false, $consignado = false) =>
            ($estado === 'REJEITADO' || (!$numero && $estado === 'DISPONIVEL'))
            && !$rascunho && !$consignado;

        $this->assertTrue($podeEnviar('REJEITADO', 123));
        $this->assertTrue($podeEnviar('REJEITADO', 0));
        $this->assertTrue($podeEnviar('DISPONIVEL', 0));
        $this->assertFalse($podeEnviar('APROVADO', 123));
        $this->assertFalse($podeEnviar('APROVADO', 0));
        $this->assertFalse($podeEnviar('CANCELADO', 0));
        $this->assertFalse($podeEnviar('DISPONIVEL', 123));
        $this->assertFalse($podeEnviar('REJEITADO', 123, true));
        $this->assertFalse($podeEnviar('REJEITADO', 123, false, true));
    }
}
