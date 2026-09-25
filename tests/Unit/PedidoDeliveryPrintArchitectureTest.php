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

    public function test_print_action_keeps_the_pdf_service_and_the_html_alternative(): void
    {
        $method = new \ReflectionMethod(
            \App\Http\Controllers\PedidoDeliveryController::class,
            'print'
        );
        $lines = file($method->getFileName());
        $source = implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        $this->assertSame(
            \App\Services\Delivery\PedidoDeliveryPrintService::class,
            $method->getParameters()[1]->getType()->getName()
        );
        $this->assertStringContainsString('$printService->render($pedido)', $source);
        $this->assertStringContainsString("'Content-Type' => 'application/pdf'", $source);
        $this->assertStringContainsString("where('empresa_id', \$this->empresa_id)", $source);
        $this->assertStringContainsString("query('formato', 'pdf') === 'html'", $source);
        $this->assertStringContainsString('pedidosDelivery/impressao_cupom', $source);
        $this->assertFileExists(resource_path('views/pedidosDelivery/impressao_cupom.blade.php'));
    }

    public function test_restored_view_renders_a_single_page_thermal_pdf(): void
    {
        // O composer global carrega avisos e menus do banco, fora do escopo
        // desta fixture. Isolamos apenas esse evento nesta aplicação de teste;
        // Blade, a view restaurada e o motor DomPDF continuam sendo reais.
        $this->app['events']->forget('composing: *');

        // Fixture independente de banco, de serviços externos e de pedidos reais.
        $dados = [
            'pedido' => (object) ['id' => 235],
            'empresaNome' => 'Empresa de Teste',
            'empresaDocumento' => null,
            'empresaTelefone' => null,
            'empresaEndereco' => 'Rua de Teste, 1',
            'logoDataUri' => null,
            'qrCodeDataUri' => null,
            'clienteNome' => 'Cliente de Teste',
            'telefone' => null,
            'enderecoEntrega' => 'Rua da Entrega, 2',
            'itens' => collect([[
                'nome' => 'Pizza de Teste',
                'quantidade' => 2.0,
                'valor_unitario' => 50.0,
                'valor_total' => 100.0,
                'tamanho' => 'Grande',
                'sabores' => collect(['Calabresa', 'Mussarela']),
                'adicionais' => collect([[
                    'nome' => 'Borda',
                    'quantidade' => 1.0,
                    'valor' => 0.0,
                ]]),
                'observacao' => 'Sem cebola',
            ]]),
            'subtotal' => 100.0,
            'desconto' => 10.0,
            'frete' => 5.0,
            'total' => 95.0,
            'formaPagamento' => 'Dinheiro',
            'trocoPara' => 100.0,
            'observacao' => 'Pedido de teste',
            'dataPedido' => \Illuminate\Support\Carbon::parse('2026-09-25 12:00:00'),
            'previsaoEntrega' => null,
        ];

        $html = view('pedidosDelivery.impressao_pedido', $dados)->render();
        $this->assertStringContainsString('PEDIDO #235', $html);
        $this->assertStringContainsString('Pizza de Teste', $html);
        $this->assertStringContainsString('R$ 95,00', $html);

        $service = new class extends \App\Services\Delivery\PedidoDeliveryPrintService {
            public function renderFixture(array $dados): array
            {
                $height = $this->resolveSinglePageHeightMm($dados);
                $pdf = $this->makePdf($dados, $height);
                $output = $pdf->output();

                return [
                    $output,
                    (int) $pdf->getDomPDF()->getCanvas()->get_page_count(),
                    (float) $pdf->getDomPDF()->getCanvas()->get_width(),
                ];
            }
        };

        [$output, $pageCount, $width] = $service->renderFixture($dados);
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertSame(1, $pageCount);
        $this->assertEqualsWithDelta(80.0 * 72 / 25.4, $width, 0.01);
    }
}
