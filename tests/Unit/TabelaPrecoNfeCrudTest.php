<?php

namespace Tests\Unit;

use App\Http\Controllers\TabelaPrecoNfeController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TabelaPrecoNfeCrudTest extends TestCase
{
    public function test_normalizacao_de_preco_preserva_casas_decimais_e_milhares(): void
    {
        $controller = new TabelaPrecoNfeController();
        $method = new ReflectionMethod($controller, 'normalizarPreco');

        $casos = [
            '12,90' => '12.90',
            '12.90' => '12.90',
            '1.250,50' => '1250.50',
            'R$ 1.250,50' => '1250.50',
            '1.250' => '1250.00',
            '1234,5' => '1234.50',
            '1234.5' => '1234.50',
            '0' => '0.00',
            '0,00' => '0.00',
            '9999999999999,99' => '9999999999999.99',
        ];

        foreach ($casos as $entrada => $esperado) {
            $this->assertSame($esperado, $method->invoke($controller, $entrada), $entrada);
        }
    }

    public function test_precos_invalidos_nao_podem_ser_salvos(): void
    {
        $controller = new TabelaPrecoNfeController();
        $method = new ReflectionMethod($controller, 'normalizarPreco');

        foreach (['', '-12,90', '12,3456', '12.3456', 'R$ x', '1.25,50', '12,3,4', '99999999999999,00'] as $entrada) {
            $this->assertNull($method->invoke($controller, $entrada), $entrada);
        }
    }

    public function test_interface_de_edicao_e_exclusao_reutiliza_rotas_e_campos_originais(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root . '/resources/views/tabela_preco_nfe/index.blade.php');
        $routes = file_get_contents($root . '/routes/web.php');

        $this->assertStringContainsString('id="kt_select2_1"', $view);
        $this->assertStringContainsString("$('#kt_select2_1').val(produtoId).trigger('change')", $view);
        $this->assertStringNotContainsString("$('#produto_id').val(produtoId)", $view);
        $this->assertStringContainsString("route('tabela-preco-nfe.delete', \$preco->id)", $view);
        $this->assertStringContainsString("@method('DELETE')", $view);
        $this->assertStringContainsString('@csrf', $view);
        $this->assertStringNotContainsString("url('/tabelaPrecoNfe/delete/'", $view);

        $this->assertStringContainsString(
            "Route::delete('/delete/{id}', 'TabelaPrecoNfeController@delete')",
            $routes
        );
    }

    public function test_controller_valida_empresa_produto_e_edicao_de_produto_com_preco(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/TabelaPrecoNfeController.php');

        $this->assertStringContainsString("->where('produtos.empresa_id', \$empresa_id)", $controller);
        $this->assertStringContainsString("->orWhereIn('id', \$produtosComPreco)", $controller);
        $this->assertStringContainsString("Produto::where('empresa_id', \$empresa_id)", $controller);
        $this->assertStringContainsString(
            "TabelaPrecoNfe::where('empresa_id', \$empresa_id)->findOrFail(\$id)",
            $controller
        );
        $this->assertStringContainsString('TabelaPrecoNfe::updateOrCreate(', $controller);
    }
}
