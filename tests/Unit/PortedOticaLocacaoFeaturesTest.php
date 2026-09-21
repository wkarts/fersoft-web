<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PortedOticaLocacaoFeaturesTest extends TestCase
{
    public function testOticaRoutesMatchCurrentControllerMethods(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = file_get_contents($root . '/routes/web.php');
        $controller = file_get_contents($root . '/app/Http/Controllers/OticaController.php');

        $this->assertStringContainsString(
            "Route::post('/cliente-rapido', 'OticaController@cadastroRapidoCliente')->name('otica.clienteRapido')",
            $routes
        );
        $this->assertStringContainsString(
            "Route::get('/buscar-cidades', 'OticaController@buscarCidades')->name('otica.buscarCidades')",
            $routes
        );
        $this->assertStringContainsString(
            "Route::post('/enviar-whatsapp-direto', 'OticaController@enviarWhatsAppDireto')->name('otica.enviarWhatsAppDireto')",
            $routes
        );

        $this->assertStringContainsString('public function cadastroRapidoCliente(', $controller);
        $this->assertStringContainsString('public function buscarCidades(', $controller);
        $this->assertStringContainsString('public function enviarWhatsAppDireto(', $controller);
    }

    public function testOticaBillingRedirectsOnlyAfterTransactionAndKeepsGeneratedPdvId(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/OticaController.php');

        $this->assertStringContainsString('$preVendaId = null;', $controller);
        $this->assertStringContainsString('$vendaId = null;', $controller);
        $this->assertStringContainsString('DB::transaction(function () use ($id, $tipo, &$preVendaId, &$vendaId)', $controller);
        $this->assertStringContainsString("redirect('/frenteCaixa?prevenda_id=' . \$preVendaId)", $controller);
        $this->assertStringContainsString("redirect('/vendas/edit/' . \$vendaId)", $controller);
        $this->assertStringContainsString("Log::error('Falha ao faturar OS de Ótica.'", $controller);
    }

    public function testLocacaoKeepsCurrentAdvancedWorkflowAndRestoresQuickCustomerRegistration(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root . '/resources/views/locacao/register.blade.php');
        $controller = file_get_contents($root . '/app/Http/Controllers/LocacaoController.php');

        // Recursos atuais que não podem regredir.
        $this->assertStringContainsString('value="locacao_cliente"', $view);
        $this->assertStringContainsString('value="coleta_fornecedor"', $view);
        $this->assertStringContainsString('name="tipo_calculo"', $view);
        $this->assertStringContainsString('name="valor_frete"', $view);
        $this->assertStringContainsString('name="quantidade_parcelas"', $view);
        $this->assertStringContainsString('public function marcarComoEntregue(', $controller);
        $this->assertStringContainsString('public function solicitarRetirada(', $controller);
        $this->assertStringContainsString('public function finalizarLocacao(', $controller);
        $this->assertStringContainsString('public function gerarFaturamentoFinanceiro(', $controller);

        // Delta recuperado da versão antiga.
        $this->assertStringContainsString('id="modal-cliente-locacao"', $view);
        $this->assertStringContainsString('id="btn-locacao-salvar-cliente"', $view);
        $this->assertStringContainsString("clientes/quickSave", $view);
        $this->assertStringContainsString('salvarClienteRapidoLocacao()', $view);
        $this->assertStringContainsString('id="kt_select2_cliente"', $view);
    }
}
