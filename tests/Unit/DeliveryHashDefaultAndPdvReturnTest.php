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

        $this->assertStringContainsString(
            'Hash curto automático (padrão)',
            $view
        );
        $this->assertStringContainsString(
            'ID da empresa (compatibilidade)',
            $view
        );
        $this->assertStringContainsString(
            'Link por ID (compatibilidade):',
            $view
        );

        $this->assertStringContainsString(
            'delivery_configs_public_link_value_unique',
            $migration
        );
        $this->assertStringContainsString(
            "DEFAULT 'hash'",
            $defaultMigration
        );
    }

    public function test_pdv_return_is_explicit_only_when_delivery_originates_from_order_screen(): void
    {
        $detail = file_get_contents(resource_path('views/pedidosDelivery/detalhe.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/PedidoDeliveryController.php'));
        $pdv = file_get_contents(resource_path('views/frontBox/main3.blade.php'));

        $this->assertStringContainsString(
            '/pedidosDelivery/irParaFrenteCaixa/{{$pedido->id}}?retorno=pedidos',
            $detail
        );
        $this->assertStringContainsString(
            "\$request->query('retorno') === 'pedidos'",
            $controller
        );
        $this->assertStringContainsString(
            "->with('retornoPosVenda', '/pedidosDelivery')",
            $controller
        );
        $this->assertStringContainsString(
            'id="retorno_pos_venda"',
            $pdv
        );
        $this->assertStringContainsString(
            "{{ \$retornoPosVenda ?? '/frenteCaixa' }}",
            $pdv
        );
    }

    public function test_completed_delivery_sale_returns_to_orders_with_or_without_nfce(): void
    {
        $js = file_get_contents(public_path('js/frenteCaixa2.js'));

        $this->assertStringContainsString(
            'function destinoPosVenda()',
            $js
        );
        $this->assertStringContainsString(
            "return path + 'frenteCaixa';",
            $js
        );
        $this->assertStringContainsString(
            'function redirecionarPosVenda()',
            $js
        );
        $this->assertStringContainsString(
            'window.location.replace(destino);',
            $js
        );

        // Venda sem cupom fiscal.
        $this->assertStringContainsString(
            "window.open(path + 'nfce/imprimirNaoFiscal/'+e.id, '_blank');",
            $js
        );

        // NFC-e normal, contingência e documento já aprovado.
        $this->assertStringContainsString(
            "else if(e == 'OFFL')",
            $js
        );
        $this->assertStringContainsString(
            "else if(e == 'Apro')",
            $js
        );
        $this->assertStringContainsString(
            "window.open(path + 'nfce/imprimir/'+vendaId, '_blank');",
            $js
        );

        // Todos esses fluxos usam o destino pós-venda preservado.
        $this->assertGreaterThanOrEqual(
            5,
            substr_count($js, 'redirecionarPosVenda();')
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
            "->lockForUpdate()",
            $vendaController
        );
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
            "$pedidoDeliveryVenda->status_pagamento === 'pago_pdv'",
            $vendaController
        );
        $this->assertStringContainsString(
            "$pedidoDeliveryVenda->status_pagamento = 'pago_pdv';",
            $vendaController
        );

        $this->assertStringContainsString(
            'Venda PDV @if(isset($vendaPdv) && $vendaPdv) #{{$vendaPdv->id}} @endif concluída',
            $detail
        );
    }

    public function test_crediario_also_marks_delivery_as_paid_and_returns_to_orders(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/VendaController.php'));
        $js = file_get_contents(public_path('js/frenteCaixa2.js'));

        $this->assertStringContainsString(
            "use App\\Models\\PedidoDelivery;",
            $controller
        );
        $this->assertStringContainsString(
            "$pedidoDeliveryVenda->status_pagamento === 'pago_pdv'",
            $controller
        );
        $this->assertStringContainsString(
            "$pedidoDeliveryVenda->status_pagamento = 'pago_pdv';",
            $controller
        );
        $this->assertStringContainsString(
            "url: path + 'vendas/salvarCrediario'",
            $js
        );
        $this->assertStringContainsString(
            'redirecionarPosVenda()',
            $js
        );
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
            "$pedido->status_pagamento === 'pago_pdv'",
            $controller
        );

        $this->assertStringContainsString(
            "window.addEventListener('pageshow'",
            $js
        );
        $this->assertStringContainsString(
            "pedidosDelivery/statusVendaPdv/",
            $js
        );
        $this->assertStringContainsString(
            "window.location.replace('/pedidosDelivery');",
            $js
        );
    }
}
