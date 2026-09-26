<?php

namespace Tests\Unit;

use Tests\TestCase;

class DeliveryHashDefaultAndPdvReturnTest extends TestCase
{
    public function test_new_delivery_config_defaults_to_short_hash(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ConfigDeliveryController.php'));
        $view = file_get_contents(resource_path('views/configDelivery/index.blade.php'));
        $migration = file_get_contents(
            database_path('migrations/2026_09_25_162500_add_public_link_fields_to_delivery_configs_table.php')
        );
        $defaultMigration = file_get_contents(
            database_path('migrations/2026_09_25_214700_set_delivery_public_link_mode_default_hash.php')
        );

        $this->assertStringContainsString(
            "\$config->public_link_mode ?? 'hash'",
            $controller
        );
        $this->assertStringContainsString(
            "\$configAtual->public_link_mode ?? 'hash'",
            $controller
        );
        $this->assertStringContainsString(
            "strtolower(Str::random(8))",
            $controller
        );
        $this->assertStringContainsString(
            "DeliveryConfig::where('public_link_value', \$valorLinkPublico)",
            $controller
        );

        $this->assertStringContainsString('Hash curto automático (padrão)', $view);
        $this->assertStringContainsString('ID da empresa (compatibilidade)', $view);
        $this->assertStringContainsString('Link por ID (compatibilidade):', $view);
        $this->assertStringContainsString('delivery_configs_public_link_value_unique', $migration);
        $this->assertStringContainsString("DEFAULT 'hash'", $defaultMigration);
    }

    public function test_pdv_return_uses_the_real_internal_calling_route(): void
    {
        $detail = file_get_contents(resource_path('views/pedidosDelivery/detalhe.blade.php'));
        $kanban = file_get_contents(resource_path('views/pedidosDelivery/kanban.blade.php'));
        $alterar = file_get_contents(resource_path('views/pedidosDelivery/alterarEstado.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/PedidoDeliveryController.php'));
        $pdv = file_get_contents(resource_path('views/frontBox/main3.blade.php'));

        $this->assertStringContainsString(
            '?retorno={{ urlencode(request()->getRequestUri()) }}',
            $detail
        );
        $this->assertStringContainsString(
            '?retorno={{ urlencode(request()->getRequestUri()) }}',
            $kanban
        );
        $this->assertStringContainsString(
            'encodeURIComponent(retornoAtual)',
            $kanban
        );

        $this->assertStringContainsString(
            'private function resolverRetornoPdv(Request $request',
            $controller
        );
        $this->assertStringContainsString(
            "\$request->headers->get('referer', '')",
            $controller
        );
        $this->assertStringContainsString(
            "strcasecmp((string) \$partes['host'], (string) \$request->getHost())",
            $controller
        );
        $this->assertStringContainsString(
            "->with('retornoPosVenda', \$retornoPosVenda)",
            $controller
        );
        $this->assertStringContainsString(
            'name="retorno" value="{{ $retornoPdv ?? \'/pedidosDelivery\' }}"',
            $alterar
        );

        $this->assertStringContainsString('id="retorno_pos_venda"', $pdv);
        $this->assertStringContainsString(
            "{{ \$retornoPosVenda ?? '/frenteCaixa' }}",
            $pdv
        );
    }

    public function test_pdv_return_rejects_external_or_intermediate_destinations(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PedidoDeliveryController.php'));

        $this->assertStringContainsString(
            "str_starts_with(\$retorno, '//')",
            $controller
        );
        $this->assertStringContainsString(
            "str_starts_with(\$caminho, '/frenteCaixa')",
            $controller
        );
        $this->assertStringContainsString(
            "str_starts_with(\$caminho, '/pedidosDelivery/irParaFrenteCaixa')",
            $controller
        );
        $this->assertStringContainsString(
            "str_starts_with(\$caminho, '/pedidosDelivery/alterarPedido')",
            $controller
        );
    }

    public function test_completed_delivery_sale_returns_to_origin_with_or_without_nfce(): void
    {
        $js = file_get_contents(public_path('js/frenteCaixa2.js'));

        $this->assertStringContainsString('function destinoPosVenda()', $js);
        $this->assertStringContainsString("return path + 'frenteCaixa';", $js);
        $this->assertStringContainsString('function redirecionarPosVenda()', $js);
        $this->assertStringContainsString('window.location.replace(destino);', $js);
        $this->assertStringContainsString('window.opener.location.href = destino;', $js);
        $this->assertStringContainsString('window.opener.focus();', $js);
        $this->assertStringContainsString('window.close();', $js);

        $this->assertStringContainsString(
            "abrirImpressaoPdv(path + 'nfce/imprimirNaoFiscal/' + e.id);",
            $js
        );
        $this->assertStringContainsString("else if(e == 'OFFL')", $js);
        $this->assertStringContainsString("else if(e == 'Apro')", $js);
        $this->assertStringContainsString(
            "abrirImpressaoPdv(path + 'nfce/imprimir/'+vendaId);",
            $js
        );

        $this->assertGreaterThanOrEqual(
            5,
            substr_count($js, 'redirecionarPosVenda();')
        );
    }

    public function test_delivery_reserves_print_tab_before_async_checkout_and_reuses_it(): void
    {
        $js = file_get_contents(public_path('js/frenteCaixa2.js'));

        $this->assertStringContainsString(
            'function reservarJanelaImpressaoDelivery()',
            $js
        );
        $this->assertStringContainsString(
            "window.open('about:blank', 'fersoft_delivery_impressao')",
            $js
        );
        $this->assertStringContainsString(
            'function abrirImpressaoPdv(url)',
            $js
        );
        $this->assertStringContainsString(
            'JANELA_IMPRESSAO_DELIVERY.location.replace(url);',
            $js
        );
        $this->assertStringContainsString(
            'reservarJanelaImpressaoDelivery();',
            $js
        );
        $this->assertStringContainsString(
            'fecharJanelaImpressaoDeliverySeVazia();',
            $js
        );
    }

    public function test_delivery_non_fiscal_print_flow_finishes_back_on_origin(): void
    {
        $js = file_get_contents(public_path('js/frenteCaixa2.js'));

        $this->assertStringContainsString(
            'function finalizarNaoFiscalDelivery(e)',
            $js
        );
        $this->assertStringContainsString(
            "abrirImpressaoPdv(path + 'nfce/imprimirNaoFiscal/' + e.id);",
            $js
        );
        $this->assertStringContainsString(
            'setTimeout(redirecionarPosVenda, 250);',
            $js
        );
        $this->assertStringContainsString(
            'finalizarNaoFiscalDelivery(e);',
            $js
        );
    }

    public function test_delivery_fiscal_error_after_saved_sale_leaves_pdv_for_origin(): void
    {
        $js = file_get_contents(public_path('js/frenteCaixa2.js'));

        $this->assertStringContainsString(
            'function sairAposErroFiscalDelivery()',
            $js
        );
        $this->assertStringContainsString(
            'sairAposErroFiscalDelivery();',
            $js
        );
        $this->assertStringContainsString(
            'redirecionarPosVenda();',
            $js
        );
    }

    public function test_paid_delivery_order_cannot_be_opened_or_sold_twice(): void
    {
        $pedidoController = file_get_contents(app_path('Http/Controllers/PedidoDeliveryController.php'));
        $vendaController = file_get_contents(app_path('Http/Controllers/VendaCaixaController.php'));
        $detail = file_get_contents(resource_path('views/pedidosDelivery/detalhe.blade.php'));

        $this->assertStringContainsString(
            "->where('pedido_delivery_id', \$pedido->id)",
            $pedidoController
        );
        $this->assertStringContainsString(
            "->where('rascunho', 0)",
            $pedidoController
        );
        $this->assertStringContainsString(
            'Este pedido já foi finalizado no PDV pela venda #',
            $pedidoController
        );
        $this->assertStringContainsString(
            "return redirect(\$retornoPosVenda);",
            $pedidoController
        );

        $this->assertStringContainsString("->lockForUpdate()", $vendaController);
        $this->assertStringContainsString(
            "->where('pedido_delivery_id', \$deliveryId)",
            $vendaController
        );
        $this->assertStringContainsString(
            "->where('rascunho', 0)",
            $vendaController
        );
        $this->assertStringContainsString(
            'Este pedido do Delivery já foi finalizado no PDV pela venda #',
            $vendaController
        );
        $this->assertStringContainsString(
            "\$pedidoDeliveryVenda->status_pagamento === 'pago_pdv'",
            $vendaController
        );
        $this->assertStringContainsString(
            "\$pedidoDeliveryVenda->status_pagamento = 'pago_pdv';",
            $vendaController
        );

        $this->assertStringContainsString(
            'Venda PDV @if(isset($vendaPdv) && $vendaPdv) #{{$vendaPdv->id}} @endif concluída',
            $detail
        );
    }

    public function test_crediario_also_marks_delivery_as_paid_and_returns_to_origin(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/VendaController.php'));
        $js = file_get_contents(public_path('js/frenteCaixa2.js'));

        $this->assertStringContainsString("use App\\Models\\PedidoDelivery;", $controller);
        $this->assertStringContainsString(
            "\$pedidoDeliveryVenda->status_pagamento === 'pago_pdv'",
            $controller
        );
        $this->assertStringContainsString(
            "\$pedidoDeliveryVenda->status_pagamento = 'pago_pdv';",
            $controller
        );
        $this->assertStringContainsString(
            "url: path + 'vendas/salvarCrediario'",
            $js
        );
        $this->assertStringContainsString(
            "abrirImpressaoPdv(path + 'vendas/imprimirPedido/'+e.id);",
            $js
        );
        $this->assertStringContainsString('redirecionarPosVenda()', $js);
    }

    public function test_browser_back_cannot_restore_a_paid_delivery_order_for_new_sale(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/PedidoDeliveryController.php'));
        $js = file_get_contents(public_path('js/frenteCaixa2.js'));

        $this->assertStringContainsString(
            "Route::get('/statusVendaPdv/{id}', 'PedidoDeliveryController@statusVendaPdv');",
            $routes
        );
        $this->assertStringContainsString(
            'public function statusVendaPdv($id)',
            $controller
        );
        $this->assertStringContainsString(
            "\$pedido->status_pagamento === 'pago_pdv'",
            $controller
        );
        $this->assertStringContainsString(
            "window.addEventListener('pageshow'",
            $js
        );
        $this->assertStringContainsString(
            'pedidosDelivery/statusVendaPdv/',
            $js
        );
        $this->assertStringContainsString(
            'redirecionarPosVenda();',
            $js
        );
    }
}
