<?php

namespace Tests\Unit;

use App\Http\Controllers\ProsoftExportController;
use App\Services\ProsoftExportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Teste de predicados sem consultar ou alterar banco de produção.
 */
class ProsoftExportIsolamentoTest extends TestCase
{
    private function codigo(string $caminho): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $caminho);
    }

    private function coletarEscopo(array $escopo, string $tabela = 'compras'): array
    {
        $controller = new ProsoftExportController(new ProsoftExportService());
        $metodo = new ReflectionMethod($controller, 'filtrarPorEscopo');
        $query = new ProsoftQueryRecorder();
        $metodo->invoke($controller, $query, $escopo, $tabela);

        return $query->condicoes;
    }

    public function test_filtro_da_matriz_nao_inclui_outras_filiais(): void
    {
        $condicoes = $this->coletarEscopo([
            'empresa_id' => 9, 'filiais_ids' => [], 'matriz' => true,
        ]);

        $this->assertSame([
            ['where', 'compras.empresa_id', 9],
            ['grupo', [['whereNull', 'compras.filial_id']]],
        ], $condicoes);
    }

    public function test_filtro_de_filial_nao_inclui_matriz_ou_outras_filiais(): void
    {
        $condicoes = $this->coletarEscopo([
            'empresa_id' => 9, 'filiais_ids' => [18], 'matriz' => false,
        ]);

        $this->assertSame([
            ['where', 'compras.empresa_id', 9],
            ['grupo', [['whereIn', 'compras.filial_id', [18]]]],
        ], $condicoes);
    }

    public function test_consolidado_mantem_apenas_matriz_e_filiais_permitidas(): void
    {
        $condicoes = $this->coletarEscopo([
            'empresa_id' => 9, 'filiais_ids' => [18, 19], 'matriz' => true,
        ]);

        $this->assertSame([
            ['where', 'compras.empresa_id', 9],
            ['grupo', [
                ['whereNull', 'compras.filial_id'],
                ['orWhereIn', 'compras.filial_id', [18, 19]],
            ]],
        ], $condicoes);
    }

    public function test_sem_locais_autorizados_nao_consulta_nada(): void
    {
        $condicoes = $this->coletarEscopo([
            'empresa_id' => 9, 'filiais_ids' => [], 'matriz' => false,
        ]);

        $this->assertSame([
            ['where', 'compras.empresa_id', 9],
            ['grupo', [['whereRaw', '1 = 0']]],
        ], $condicoes);
    }

    public function test_service_nao_gera_arquivo_sem_escopo_configurado(): void
    {
        $service = new ProsoftExportService();
        $metodo = new ReflectionMethod($service, 'comprasNoEscopo');

        $this->expectException(\LogicException::class);
        $metodo->invoke($service, [1], ['itens']);
    }

    public function test_todos_os_arquivos_e_faturas_possuem_escopo(): void
    {
        $service = $this->codigo('app/Services/ProsoftExportService.php');

        $this->assertSame(7, substr_count($service, '$this->comprasNoEscopo($compraIds'));
        $this->assertStringContainsString("->where('compras.empresa_id', \$this->empresaId)", $service);
        $this->assertStringContainsString("->where('filial_id', \$compra->filial_id)", $service);
        $this->assertStringContainsString("->where('empresa_id', \$this->empresaId)->where('id', \$compra->categoria_conta_id)", $service);
        $this->assertStringContainsString("'itens.produto'", $service);
    }

    public function test_controller_recusa_ids_injetados_e_nao_usa_empresa_do_request(): void
    {
        $controller = $this->codigo('app/Http/Controllers/ProsoftExportController.php');

        $this->assertStringNotContainsString('__empresa_id_logada() ?? $request->empresa_id', $controller);
        $this->assertStringContainsString("session('user_logged')", $controller);
        $this->assertStringContainsString("->whereIn('compras.id', \$compraIds)", $controller);
        $this->assertStringContainsString('count($permitidas) !== count($compraIds)', $controller);
        $this->assertStringContainsString("'message' => 'Existem notas fora da empresa, filial ou período autorizado.", $controller);
        $this->assertStringContainsString('definirEscopo(', $controller);
        $this->assertStringContainsString('Str::uuid()', $controller);
        $this->assertStringContainsString("storage_path('app/prosoft_export_'", $controller);
        $this->assertStringContainsString("abort_unless(in_array(\$filialId, \$filiaisIds, true), 403", $controller);
    }

    public function test_telas_nao_exibem_filiais_de_outras_empresas(): void
    {
        foreach ([
            'resources/views/relatorios/prosoft-export.blade.php',
            'resources/views/prosoft/apuracao_pis_cofins.blade.php',
        ] as $arquivo) {
            $view = $this->codigo($arquivo);
            $this->assertStringNotContainsString('Filial::all()', $view);
            $this->assertStringContainsString('@foreach($filiais as $f)', $view);
        }

        $form = $this->codigo('resources/views/relatorios/prosoft-export.blade.php');
        $this->assertStringContainsString('name="data_inicio" value="{{ $dataInicio }}"', $form);
        $this->assertStringContainsString('name="data_fim" value="{{ $dataFim }}"', $form);
        $this->assertStringContainsString('name="filial_id" value="{{ $filial_id ??', $form);
    }
}

/** Test double apenas para inspecionar os predicados SQL construídos. */
class ProsoftQueryRecorder
{
    public array $condicoes = [];

    public function where($coluna, $valor = null): self
    {
        if ($coluna instanceof \Closure) {
            $filha = new self();
            $coluna($filha);
            $this->condicoes[] = ['grupo', $filha->condicoes];
        } else {
            $this->condicoes[] = ['where', $coluna, $valor];
        }

        return $this;
    }

    public function whereNull(string $coluna): self
    {
        $this->condicoes[] = ['whereNull', $coluna];
        return $this;
    }

    public function whereIn(string $coluna, array $ids): self
    {
        $this->condicoes[] = ['whereIn', $coluna, $ids];
        return $this;
    }

    public function orWhereIn(string $coluna, array $ids): self
    {
        $this->condicoes[] = ['orWhereIn', $coluna, $ids];
        return $this;
    }

    public function whereRaw(string $sql): self
    {
        $this->condicoes[] = ['whereRaw', $sql];
        return $this;
    }
}
