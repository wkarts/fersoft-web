<?php

namespace Tests\Unit;

use Tests\TestCase;

class DeliveryCheckoutAndPopupRegressionTest extends TestCase
{
    public function test_checkout_recognizes_all_visible_payment_methods(): void
    {
        $js = file_get_contents(public_path('jsd/forma_pagamento.js'));

        $this->assertStringContainsString(
            'return $(\'input[name="gridRadios"]:checked\').val() || \'\';',
            $js
        );
        $this->assertStringContainsString(
            "$('#maquineta, #pix').click",
            $js
        );
        $this->assertStringContainsString(
            'let formaPagamento = formaPagamentoSelecionada();',
            $js
        );
    }

    public function test_checkout_persists_delivery_selection_and_validates_payment_on_backend(): void
    {
        $view = file_get_contents(resource_path('views/delivery/forma_pagamento.blade.php'));
        $js = file_get_contents(public_path('jsd/forma_pagamento.js'));
        $controller = file_get_contents(app_path('Http/Controllers/CarrinhoController.php'));

        $this->assertStringContainsString('id="endereco_selecionado"', $view);
        $this->assertStringContainsString("$('#endereco_selecionado').val(id);", $js);
        $this->assertStringContainsString(
            'enderecoSelecionado = enderecoSelecionadoAtual();',
            $js
        );
        $this->assertStringContainsString(
            "'data.forma_pagamento' => 'required|in:maquineta,pix,dinheiro,pagseguro'",
            $controller
        );
    }

    public function test_pagseguro_is_not_initialized_when_online_payment_is_not_available(): void
    {
        $js = file_get_contents(public_path('jsd/forma_pagamento.js'));
        $controller = file_get_contents(app_path('Http/Controllers/PagSeguroController.php'));

        $this->assertStringContainsString(
            "if($('#pagseguro').length > 0 && typeof PagSeguroDirectPayment !== 'undefined')",
            $js
        );
        $this->assertStringContainsString("if(!env('PAGSEGURO_ATIVO'))", $controller);
        $this->assertStringContainsString('simplexml_load_string($body)', $controller);
        $this->assertStringContainsString(
            'PagSeguro retornou uma resposta inválida.',
            $controller
        );
    }

    public function test_new_order_popup_waits_for_jquery_and_uses_existing_status_endpoint(): void
    {
        $layout = file_get_contents(resource_path('views/default/layout.blade.php'));

        $this->assertStringContainsString("window.addEventListener('load'", $layout);
        $this->assertStringContainsString(
            "typeof window.jQuery === 'undefined'",
            $layout
        );
        $this->assertStringContainsString(
            "url: '/pedidosDelivery/actualizarStatusKanban'",
            $layout
        );
        $this->assertStringContainsString(
            "alterarStatusPedido('aprovado'",
            $layout
        );
        $this->assertStringContainsString(
            "alterarStatusPedido('cancelado'",
            $layout
        );
        $this->assertStringContainsString(
            "response.forma_pagamento_label || response.forma_pagamento",
            $layout
        );
    }

    public function test_new_order_payload_is_company_scoped_unread_and_complete(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PedidoDeliveryController.php'));

        $this->assertStringContainsString(
            "->where('empresa_id', \$this->empresa_id)",
            $controller
        );
        $this->assertStringContainsString(
            "->where('pedido_lido', false)",
            $controller
        );
        $this->assertStringContainsString(
            "->where('forma_pagamento', '<>', '')",
            $controller
        );
        $this->assertStringContainsString(
            "'valor_total' => \$valorFormatado",
            $controller
        );
        $this->assertStringContainsString(
            "'forma_pagamento' => \$pedido->forma_pagamento",
            $controller
        );
        $this->assertStringContainsString(
            "'forma_pagamento_label' => \$formaPagamentoLabel",
            $controller
        );
        $this->assertStringContainsString(
            "'tipo_entrega' => \$pedido->endereco_id ? 'Entrega' : 'Retirada no balcão'",
            $controller
        );
        $this->assertStringContainsString(
            "'endereco' => \$enderecoEntrega",
            $controller
        );
        $this->assertStringContainsString(
            "'observacao' => \$pedido->observacao ?? ''",
            $controller
        );
        $this->assertStringContainsString(
            "'itens' => \$pedido->itens->map",
            $controller
        );
    }

    public function test_accept_and_reject_save_status_even_if_whatsapp_fails(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PedidoDeliveryController.php'));

        $this->assertStringContainsString(
            "\$estadosPermitidos = ['aprovado', 'cancelado', 'finalizado', 'entregue', 'finalizar_caixa'];",
            $controller
        );
        $this->assertStringContainsString(
            "\$pedido->pedido_lido = true;",
            $controller
        );
        $this->assertStringContainsString(
            "\$pedido->motivoEstado = trim((string) (\$request->motivo ?? ''));",
            $controller
        );
        $this->assertStringContainsString(
            'Status do delivery salvo, mas o WhatsApp não foi enviado.',
            $controller
        );
        $this->assertStringContainsString(
            "'sucesso' => true",
            $controller
        );
    }

    public function test_legacy_order_list_monitor_does_not_duplicate_global_popup(): void
    {
        $view = file_get_contents(resource_path('views/pedidosDelivery/list.blade.php'));

        $this->assertStringContainsString(
            "if (document.getElementById('modalNovoPedidoAlerta'))",
            $view
        );
    }

    public function test_google_maps_is_loaded_before_config_delivery_inline_initializer(): void
    {
        $view = file_get_contents(resource_path('views/configDelivery/index.blade.php'));

        $this->assertStringContainsString(
            '<script src="https://maps.googleapis.com/maps/api/js?key={{env(\'API_KEY_MAPS\')}}"></script>',
            $view
        );
        $this->assertStringNotContainsString(
            'maps.googleapis.com/maps/api/js?key={{env(\'API_KEY_MAPS\')}}" async defer',
            $view
        );
    }

    public function test_tracking_menu_does_not_use_cart_order_variable(): void
    {
        $layout = file_get_contents(resource_path('views/delivery/default.blade.php'));

        $this->assertStringContainsString(
            "\$pedidoAtivoId = session('ultimo_pedido_id') ?? null;",
            $layout
        );
        $this->assertStringNotContainsString(
            "\$pedido->id ?? session('ultimo_pedido_id')",
            $layout
        );
    }

    public function test_my_orders_and_tracking_exclude_carts_still_being_built(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/CarrinhoController.php'));

        $this->assertStringContainsString(
            "->where('valor_total', '>', 0)",
            $controller
        );
        $this->assertStringContainsString(
            "->where('forma_pagamento', '<>', '')",
            $controller
        );
        $this->assertStringContainsString(
            'Pedido não encontrado ou ainda não foi finalizado.',
            $controller
        );
    }

    public function test_tracking_has_all_states_without_full_page_auto_reload(): void
    {
        $view = file_get_contents(resource_path('views/delivery/pedido_finalizado.blade.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/CarrinhoController.php'));

        $this->assertStringContainsString('id="status-passo-1"', $view);
        $this->assertStringContainsString('id="status-passo-2"', $view);
        $this->assertStringContainsString('id="status-passo-3"', $view);
        $this->assertStringContainsString('id="status-passo-4"', $view);
        $this->assertStringContainsString('id="pedido-cancelado"', $view);
        $this->assertStringContainsString("estado === 'cancelado'", $view);
        $this->assertStringContainsString("estado === 'aprovado'", $view);
        $this->assertStringContainsString("estado === 'finalizado'", $view);
        $this->assertStringContainsString('setInterval(function()', $view);
        $this->assertStringNotContainsString('window.location.reload();', $view);

        $this->assertStringContainsString(
            "Route::get('/status/{id}', 'CarrinhoController@statusPedido');",
            $routes
        );
        $this->assertStringContainsString(
            'public function statusPedido($id)',
            $controller
        );
    }

    public function test_tracking_displays_pix_and_pagseguro_orders_use_real_novo_state(): void
    {
        $view = file_get_contents(resource_path('views/delivery/pedido_finalizado.blade.php'));
        $pagSeguro = file_get_contents(app_path('Http/Controllers/PagSeguroController.php'));

        $this->assertStringContainsString(
            "\$pedido->forma_pagamento == 'pix'",
            $view
        );
        $this->assertStringContainsString(
            "\$nome_pagamento = \"Pix\";",
            $view
        );
        $this->assertStringNotContainsString(
            "->where('estado', 'nv')",
            $pagSeguro
        );
        $this->assertStringContainsString(
            "->where('estado', 'novo')",
            $pagSeguro
        );
        $this->assertStringContainsString(
            "session(['ultimo_pedido_id' => \$pedido->id]);",
            $pagSeguro
        );
    }

    public function test_pagseguro_relation_uses_the_real_model_namespace(): void
    {
        $model = file_get_contents(app_path('Models/PedidoDelivery.php'));

        $this->assertStringContainsString(
            "App\\Models\\PedidoPagSeguro",
            $model
        );
        $this->assertStringNotContainsString(
            "App\\Moddels\\PedidoPagSeguro",
            $model
        );
    }

    public function test_delivery_logout_clears_the_last_tracked_order(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString(
            "session()->forget(['telefone_cliente', 'cliente_log', 'empresa_id', 'ultimo_pedido_id']);",
            $routes
        );
    }
}
