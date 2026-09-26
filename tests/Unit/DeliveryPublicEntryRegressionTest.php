<?php

namespace Tests\Unit;

use Tests\TestCase;

class DeliveryPublicEntryRegressionTest extends TestCase
{
    public function test_delivery_config_status_does_not_block_public_entry_or_cart(): void
    {
        $deliveryController = file_get_contents(app_path('Http/Controllers/DeliveryController.php'));
        $cartController = file_get_contents(app_path('Http/Controllers/CarrinhoController.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringNotContainsString(
            "DeliveryConfig::where('empresa_id', \$this->empresa_id)->where('status', 1)",
            $deliveryController
        );

        $this->assertStringNotContainsString(
            "DeliveryConfig::where('empresa_id', \$empresa_id)->where('status', 1)",
            $cartController
        );

        $this->assertStringNotContainsString(
            "DeliveryConfig::where('empresa_id', \$empresa->id)->where('status', 1)",
            $routes
        );
    }

    public function test_direct_cardapio_requires_company_and_whatsapp_context(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/DeliveryController.php'));

        $this->assertStringContainsString(
            "\$this->empresa_id = session('empresa_id');",
            $controller
        );

        $this->assertStringContainsString(
            'Cardápio indisponível. Acesse pelo link da empresa.',
            $controller
        );

        $this->assertStringContainsString(
            "!session('telefone_cliente') && !session('cliente_log')",
            $controller
        );

        $this->assertStringContainsString(
            'Você precisa informar um WhatsApp para continuar.',
            $controller
        );
    }

    public function test_public_link_is_not_a_free_form_full_url(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ConfigDeliveryController.php'));
        $view = file_get_contents(resource_path('views/configDelivery/index.blade.php'));

        $this->assertStringContainsString(
            "url('/pedir/' . rawurlencode((string) \$identificadorLinkPublico))",
            $controller
        );

        $this->assertStringContainsString('name="public_link_mode"', $view);
        $this->assertStringContainsString('name="public_link_value"', $view);
        $this->assertStringContainsString('id="delivery_public_link"', $view);
        $this->assertStringContainsString('readonly', $view);
        $this->assertStringContainsString('Hash curto automático (padrão)', $view);
        $this->assertStringContainsString('ID da empresa (compatibilidade)', $view);
        $this->assertStringContainsString('Slug personalizado', $view);
        $this->assertStringContainsString('Token automático', $view);
        $this->assertStringContainsString('Link por ID (compatibilidade):', $view);
    }

    public function test_public_link_modes_are_persisted_with_safe_defaults(): void
    {
        $model = file_get_contents(app_path('Models/DeliveryConfig.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ConfigDeliveryController.php'));
        $migration = file_get_contents(
            database_path('migrations/2026_09_25_162500_add_public_link_fields_to_delivery_configs_table.php')
        );

        $this->assertStringContainsString("'public_link_mode', 'public_link_value'", $model);
        $this->assertStringContainsString("'public_link_mode' => 'nullable|in:auto,slug,hash,token'", $controller);
        $this->assertStringContainsString("Str::slug((string) \$request->public_link_value)", $controller);
        $this->assertStringContainsString("strtolower(Str::random(8))", $controller);
        $this->assertStringContainsString("bin2hex(random_bytes(16))", $controller);

        $this->assertStringContainsString("->default('auto')", $migration);
        $this->assertStringContainsString("->nullable()", $migration);
        $this->assertStringContainsString("delivery_configs_public_link_value_unique", $migration);
    }

    public function test_routes_resolve_custom_identifier_and_keep_company_id_link(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString(
            "->where('public_link_value', \$parametro)",
            $routes
        );

        $this->assertStringContainsString(
            "if (ctype_digit((string) \$parametro))",
            $routes
        );

        $this->assertStringContainsString(
            "->where('id', (int) \$parametro)",
            $routes
        );

        $this->assertStringContainsString(
            "return redirect('/pedir/' . rawurlencode((string) \$parametro))",
            $routes
        );
    }
}
