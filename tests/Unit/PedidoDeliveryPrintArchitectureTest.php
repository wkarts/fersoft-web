<?php

namespace Tests\Unit;

use Tests\TestCase;

class PedidoDeliveryPrintArchitectureTest extends TestCase
{
    public function test_delivery_print_does_not_depend_on_custom_sped_da_class(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/PedidoDeliveryController.php')
        );

        $this->assertStringNotContainsString(
            'NFePHP\\DA\\NFe\\PedidoPrint',
            $controller
        );

        $this->assertStringContainsString(
            'App\\Services\\Delivery\\PedidoDeliveryPrintService',
            $controller
        );
    }

    public function test_delivery_print_is_owned_by_the_application(): void
    {
        $servicePath = app_path('Services/Delivery/PedidoDeliveryPrintService.php');
        $viewPath = resource_path('views/pedidosDelivery/impressao_pedido.blade.php');

        $this->assertFileExists($servicePath);
        $this->assertFileExists($viewPath);

        $service = file_get_contents($servicePath);

        $this->assertStringContainsString(
            'Barryvdh\\DomPDF\\Facade\\Pdf',
            $service
        );

        $this->assertStringNotContainsString(
            'NFePHP\\DA\\',
            $service
        );
    }

    public function test_sped_da_remains_the_official_composer_dependency(): void
    {
        $composer = json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertArrayHasKey('nfephp-org/sped-da', $composer['require']);

        foreach ($composer['repositories'] ?? [] as $repository) {
            $raw = json_encode($repository, JSON_UNESCAPED_SLASHES);

            $this->assertStringNotContainsString('custom-sped-da', $raw);
        }
    }
}
