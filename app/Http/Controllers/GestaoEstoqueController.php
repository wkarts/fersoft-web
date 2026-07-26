<?php

namespace App\Http\Controllers;

use App\Services\EstoqueFisicoPesagemService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GestaoEstoqueController extends BaseController
{
    protected $redirectPage = '/gestao-estoque';
    protected $formTitle = 'Gestão de Estoque Físico';

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('estoque_fisico_movimentos')) {
            return redirect()->back()->with(
                'mensagem_erro',
                'A tabela de estoque físico ainda não existe. Execute as migrations da atualização.'
            );
        }

        [$dataInicial, $dataFinal] = $this->periodo($request);
        $produtoId = $request->filled('produto_id') ? (int) $request->produto_id : null;
        $parceiroNome = trim((string) $request->input('parceiro_nome', ''));
        $tipoMovimento = in_array($request->input('tipo'), ['entrada', 'saida'], true)
            ? $request->input('tipo')
            : null;
        $filialId = $this->normalizarFilialFiltro($request->input('filial_id'));

        $produtos = DB::table('produtos')
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $filiais = DB::table('filials')
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get(['id', 'descricao']);

        $queryBase = $this->queryMovimentos($produtoId, $parceiroNome, $tipoMovimento, $filialId)
            ->whereBetween('estoque_fisico_movimentos.data_movimento', [$dataInicial, $dataFinal]);

        $analitico = (clone $queryBase)
            ->select(
                'estoque_fisico_movimentos.*',
                'produtos.nome as produto_nome',
                'pesagens.id as ticket_id',
                'fornecedors.razao_social as fornecedor_nome',
                'clientes.razao_social as cliente_nome',
                'filials.descricao as filial_nome'
            )
            ->orderByDesc('estoque_fisico_movimentos.data_movimento')
            ->orderByDesc('estoque_fisico_movimentos.id')
            ->paginate(50)
            ->appends($request->query());

        $dadosPorParceiro = (clone $queryBase)
            ->select(
                'estoque_fisico_movimentos.*',
                'produtos.nome as produto_nome',
                'pesagens.id as ticket_id',
                'fornecedors.razao_social as fornecedor_nome',
                'clientes.razao_social as cliente_nome'
            )
            ->orderByDesc('estoque_fisico_movimentos.data_movimento')
            ->get();

        $resumoParceiros = [];
        foreach ($dadosPorParceiro->groupBy(fn ($item) => $item->fornecedor_nome ?: ($item->cliente_nome ?: 'Movimento manual')) as $nome => $movimentos) {
            $pesoLiquido = (float) $movimentos->sum('quantidade');
            $valorTotal = (float) $movimentos->sum('valor_total');
            $resumoParceiros[$nome] = [
                'movimentos' => $movimentos,
                'peso_bruto' => (float) $movimentos->sum('peso_bruto'),
                'impureza' => (float) $movimentos->sum('peso_impureza'),
                'peso_liquido' => $pesoLiquido,
                'valor_total' => $valorTotal,
                'preco_medio' => $pesoLiquido > 0 ? ($valorTotal / $pesoLiquido) : 0,
            ];
        }

        $querySintetico = $this->queryMovimentos($produtoId, $parceiroNome, $tipoMovimento, $filialId)
            ->where('estoque_fisico_movimentos.data_movimento', '<=', $dataFinal);

        $sinteticoRaw = $querySintetico
            ->select('produtos.id as produto_id', 'produtos.nome as produto_nome')
            ->selectRaw(
                "SUM(CASE WHEN estoque_fisico_movimentos.data_movimento < ? AND estoque_fisico_movimentos.tipo = 'entrada' THEN estoque_fisico_movimentos.quantidade ELSE 0 END) - " .
                "SUM(CASE WHEN estoque_fisico_movimentos.data_movimento < ? AND estoque_fisico_movimentos.tipo = 'saida' THEN estoque_fisico_movimentos.quantidade ELSE 0 END) AS estoque_inicial",
                [$dataInicial, $dataInicial]
            )
            ->selectRaw(
                "SUM(CASE WHEN estoque_fisico_movimentos.data_movimento BETWEEN ? AND ? AND estoque_fisico_movimentos.tipo = 'entrada' THEN estoque_fisico_movimentos.quantidade ELSE 0 END) AS entradas_periodo",
                [$dataInicial, $dataFinal]
            )
            ->selectRaw(
                "SUM(CASE WHEN estoque_fisico_movimentos.data_movimento BETWEEN ? AND ? AND estoque_fisico_movimentos.tipo = 'saida' THEN estoque_fisico_movimentos.quantidade ELSE 0 END) AS saidas_periodo",
                [$dataInicial, $dataFinal]
            )
            ->selectRaw(
                "SUM(CASE WHEN estoque_fisico_movimentos.data_movimento BETWEEN ? AND ? AND estoque_fisico_movimentos.tipo = 'entrada' THEN estoque_fisico_movimentos.valor_total ELSE 0 END) AS valor_entradas_periodo",
                [$dataInicial, $dataFinal]
            )
            ->groupBy('produtos.id', 'produtos.nome')
            ->get();

        $sintetico = $sinteticoRaw->map(function ($item) {
            $item->estoque_inicial = (float) $item->estoque_inicial;
            $item->entradas_periodo = (float) $item->entradas_periodo;
            $item->saidas_periodo = (float) $item->saidas_periodo;
            $item->saldo_atual = $item->estoque_inicial + $item->entradas_periodo - $item->saidas_periodo;
            $item->preco_medio = $item->entradas_periodo > 0
                ? ((float) $item->valor_entradas_periodo / $item->entradas_periodo)
                : 0;
            $item->valor_total_estoque = $item->saldo_atual * $item->preco_medio;
            return $item;
        })->filter(fn ($item) => $item->estoque_inicial != 0 || $item->entradas_periodo != 0 || $item->saidas_periodo != 0 || $item->saldo_atual != 0)
            ->values();

        $totais = [
            'entradas_kg' => (float) $sintetico->sum('entradas_periodo'),
            'saidas_kg' => (float) $sintetico->sum('saidas_periodo'),
            'saldo_kg' => (float) $sintetico->sum('saldo_atual'),
            'valor_patrimonio' => (float) $sintetico->sum('valor_total_estoque'),
        ];

        $title = $this->formTitle;

        if ($request->input('export') === 'pdf') {
            $html = view('estoque.exportar', compact('title', 'analitico', 'sintetico', 'totais', 'dataInicial', 'dataFinal'))->render();
            $dompdf = new \Dompdf\Dompdf(['enable_remote' => true]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="estoque-fisico.pdf"',
            ]);
        }

        if ($request->input('export') === 'excel') {
            $conteudo = view('estoque.exportar', compact('title', 'analitico', 'sintetico', 'totais', 'dataInicial', 'dataFinal'))->render();
            return response($conteudo, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="estoque-fisico.xls"',
            ]);
        }

        return view('estoque.index', compact(
            'title',
            'analitico',
            'sintetico',
            'resumoParceiros',
            'totais',
            'dataInicial',
            'dataFinal',
            'produtos',
            'filiais',
            'filialId'
        ));
    }

    public function sincronizar(Request $request, EstoqueFisicoPesagemService $service)
    {
        try {
            $resultado = $service->sincronizarEmpresa($this->empresa_id, $this->usuario_id);

            $mensagem = "Sincronização concluída: {$resultado['processadas']} pesagens processadas e {$resultado['inseridos']} movimentos novos.";
            if ($resultado['erros']) {
                $mensagem .= ' Algumas pesagens apresentaram erro. Consulte os logs.';
                \Log::warning('Falhas parciais na sincronização do estoque físico', [
                    'empresa_id' => $this->empresa_id,
                    'erros' => $resultado['erros'],
                ]);
            }

            return redirect()->back()->with('mensagem_sucesso', $mensagem);
        } catch (\Throwable $e) {
            \Log::error('Erro ao sincronizar estoque físico', [
                'empresa_id' => $this->empresa_id,
                'erro' => $e->getMessage(),
            ]);

            return redirect()->back()->with('mensagem_erro', 'Não foi possível sincronizar o estoque físico: ' . $e->getMessage());
        }
    }

    private function queryMovimentos(?int $produtoId, string $parceiroNome, ?string $tipoMovimento, $filialId)
    {
        $query = DB::table('estoque_fisico_movimentos')
            ->join('produtos', function ($join): void {
                $join->on('estoque_fisico_movimentos.produto_id', '=', 'produtos.id')
                    ->on('estoque_fisico_movimentos.empresa_id', '=', 'produtos.empresa_id');
            })
            ->leftJoin('pesagens', function ($join): void {
                $join->on('estoque_fisico_movimentos.pesagem_id', '=', 'pesagens.id')
                    ->on('estoque_fisico_movimentos.empresa_id', '=', 'pesagens.empresa_id');
            })
            ->leftJoin('fornecedors', function ($join): void {
                $join->on('pesagens.fornecedor_id', '=', 'fornecedors.id')
                    ->on('pesagens.empresa_id', '=', 'fornecedors.empresa_id');
            })
            ->leftJoin('clientes', function ($join): void {
                $join->on('pesagens.cliente_id', '=', 'clientes.id')
                    ->on('pesagens.empresa_id', '=', 'clientes.empresa_id');
            })
            ->leftJoin('filials', 'estoque_fisico_movimentos.filial_id', '=', 'filials.id')
            ->where('estoque_fisico_movimentos.empresa_id', $this->empresa_id);

        if ($produtoId) {
            $query->where('estoque_fisico_movimentos.produto_id', $produtoId);
        }
        if ($tipoMovimento) {
            $query->where('estoque_fisico_movimentos.tipo', $tipoMovimento);
        }
        if ($parceiroNome !== '') {
            $query->where(function ($subquery) use ($parceiroNome): void {
                $subquery->where('fornecedors.razao_social', 'like', "%{$parceiroNome}%")
                    ->orWhere('clientes.razao_social', 'like', "%{$parceiroNome}%");
            });
        }
        if ($filialId === 'matriz') {
            $query->whereNull('estoque_fisico_movimentos.filial_id');
        } elseif (is_int($filialId)) {
            $query->where('estoque_fisico_movimentos.filial_id', $filialId);
        }

        return $query;
    }

    private function periodo(Request $request): array
    {
        try {
            $inicial = Carbon::parse($request->input('data_inicial', now()->startOfMonth()->toDateString()))->toDateString();
            $final = Carbon::parse($request->input('data_final', now()->toDateString()))->toDateString();
        } catch (\Throwable) {
            $inicial = now()->startOfMonth()->toDateString();
            $final = now()->toDateString();
        }

        if ($inicial > $final) {
            [$inicial, $final] = [$final, $inicial];
        }

        return [$inicial, $final];
    }

    private function normalizarFilialFiltro($valor)
    {
        if ($valor === 'matriz') {
            return 'matriz';
        }

        $id = (int) $valor;
        if ($id <= 0) {
            return null;
        }

        return DB::table('filials')
            ->where('empresa_id', $this->empresa_id)
            ->where('id', $id)
            ->exists() ? $id : null;
    }
}
