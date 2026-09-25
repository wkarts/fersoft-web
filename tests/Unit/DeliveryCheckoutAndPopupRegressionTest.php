<?php

namespace Tests\Unit;

use Tests\TestCase;

class DeliveryCheckoutAndPopupRegressionTest extends TestCase
{
    public function test_checkout_recognizes_all_visible_payment_methods(): void
    {
        $js = file_get_contents(public_path('jsd/forma_pagamento.js'));

        $this->assertStringContainsString(
            "return $('input[name="gridRadios"]:checked').val() || '';",
            $js
        );
        $this->assertStringContainsString("'#maquineta, #pix'", str_replace('"', "'", $js));
        $this->assertStringContainsString("let formaPagamento = formaPagamentoSelecionada();", $js);
    }

    public function test_checkout_persists_delivery_selection_and_does_not_lose_it_on_finalize(): void
    {
        $view = file_get_contents(resource_path('views/delivery/forma_pagamento.blade.php'));
        $js = file_get_contents(public_path('jsd/forma_pagamento.js'));

        $this->assertStringContainsString('id="endereco_selecionado"', $view);
        $this->assertStringContainsString("$('#endereco_selecionado').val(id);", $js);
        $this->assertStringContainsString('enderecoSelecionado = enderecoSelecionadoAtual();', $js);
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
        $this->assertStringContainsString("PagSeguro retornou uma resposta inválida.", $controller);
    }

    public function test_new_order_popup_waits_for_jquery_and_uses_existing_status_endpoint(): void
    {
        $layout = file_get_contents(resource_path('views/default/layout.blade.php'));

        $this->assertStringContainsString("window.addEventListener('load'", $layout);
        $this->assertStringContainsString("typeof window.jQuery === 'undefined'", $layout);
        $this->assertStringContainsString(
            "url: '/pedidosDelivery/actualizarStatusKanban'",
            $layout
        );
        $this->assertStringContainsString("alterarStatusPedido('aprovado'", $layout);
        $this->assertStringContainsString("alterarStatusPedido('cancelado'", $layout);
    }

    public function test_new_order_payload_is_company_scoped_and_has_the_fields_used_by_popup(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PedidoDeliveryController.php'));

        $this->assertStringContainsString("->where('empresa_id', \$this->empresa_id)", $controller);
        $this->assertStringContainsString("->where('forma_pagamento', '<>', '')", $controller);
        $this->assertStringContainsString("'valor_total' => \$valorFormatado", $controller);
        $this->assertStringContainsString("'forma_pagamento' => \$pedido->forma_pagamento", $controller);
        $this->assertStringContainsString("'tipo_entrega' => \$pedido->endereco_id ? 'Entrega' : 'Retirada no balcão'", $controller);
        $this->assertStringContainsString("'itens' => \$pedido->itens->map", $controller);
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
}
