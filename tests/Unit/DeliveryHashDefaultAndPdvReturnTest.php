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

        // O índice único já existente continua garantindo exclusividade no banco.
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
            "'/pedidosDelivery'",
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

    public function test_pdv_success_redirect_uses_return_destination_without_changing_default_flow(): void
    {
        $js = file_get_contents(public_path('js/frenteCaixa2.js'));

        $this->assertStringContainsString(
            "function destinoPosVenda()",
            $js
        );
        $this->assertStringContainsString(
            "return path + 'frenteCaixa';",
            $js
        );
        $this->assertStringContainsString(
            "function redirecionarPosVenda()",
            $js
        );
        $this->assertStringContainsString(
            "location.href = destinoPosVenda();",
            $js
        );
        $this->assertStringContainsString(
            "redirecionarPosVenda();",
            $js
        );
    }
}
