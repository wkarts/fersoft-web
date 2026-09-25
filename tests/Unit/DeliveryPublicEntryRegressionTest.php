<?php

namespace Tests\Unit;

use Tests\TestCase;

class DeliveryPublicEntryRegressionTest extends TestCase
{
    public function test_delivery_config_status_does_not_block_historical_public_entry(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/DeliveryController.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringNotContainsString(
            "DeliveryConfig::where('empresa_id', \$this->empresa_id)->where('status', 1)",
            $controller
        );

        $this->assertStringNotContainsString(
            "DeliveryConfig::where('empresa_id', \$empresa->id)->where('status', 1)",
            $routes
        );

        $this->assertStringContainsString(
            "DeliveryConfig::where('empresa_id', \$empresa->id)->first()",
            $routes
        );
    }

    public function test_config_delivery_exposes_existing_company_public_link_without_migration(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ConfigDeliveryController.php'));
        $view = file_get_contents(resource_path('views/configDelivery/index.blade.php'));

        $this->assertStringContainsString(
            "url('/pedir/' . \$this->empresa_id)",
            $controller
        );

        $this->assertStringContainsString('Link público do cardápio', $view);
        $this->assertStringContainsString('delivery_public_link', $view);
        $this->assertStringContainsString('Abrir cardápio', $view);
    }

    public function test_direct_cardapio_still_requires_company_context_instead_of_guessing_first_company(): void
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

        $this->assertStringNotContainsString(
            "\$this->config = DeliveryConfig::first();\n                return \$next(\$request);\n            }\n\n            \$this->empresa_id = session('empresa_id');\n            \$this->config = DeliveryConfig::first();",
            $controller
        );
    }
}
