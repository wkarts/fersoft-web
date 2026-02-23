<?php

namespace App\Http\Controllers;

use App\Models\Filial;
use App\Models\Produto;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockKardexController extends BaseController
{
    protected $redirectPage = '/estoque/kardex';


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
        $dataInicio = $request->input('data_inicio', now()->startOfMonth()->toDateString());
        $dataFim = $request->input('data_fim', now()->toDateString());
        $contexto = strtoupper($request->input('contexto', 'TODOS'));
        $produtoIdFiltro = $request->filled('produto_id') ? (int) $request->produto_id : null;

        $filialFiltro = $request->filled('filial_id')
            ? (int) $request->input('filial_id')
            : $this->filial_id;

        $base = StockMovement::query()->where('empresa_id', $this->empresa_id);
        if (!empty($filialFiltro)) {
            $base->where('filial_id', $filialFiltro);
        }
        if ($contexto !== 'TODOS') {
            $base->where('contexto', $contexto);
        }
        if (!empty($produtoIdFiltro)) {
            $base->where('produto_id', $produtoIdFiltro);
        }

        $baseInicial = clone $base;
        $iniciais = $baseInicial
            ->where('movimentado_em', '<', $dataInicio . ' 00:00:00')
            ->selectRaw('produto_id, SUM(CASE WHEN tipo = "entrada" THEN quantidade ELSE -quantidade END) as saldo_inicial')
            ->groupBy('produto_id')
            ->pluck('saldo_inicial', 'produto_id');

        $basePeriodo = clone $base;
        $periodoRows = $basePeriodo
            ->where('movimentado_em', '>=', $dataInicio . ' 00:00:00')
            ->where('movimentado_em', '<=', $dataFim . ' 23:59:59')
            ->selectRaw('produto_id,
                SUM(CASE WHEN tipo = "entrada" THEN quantidade ELSE 0 END) as entradas,
                SUM(CASE WHEN tipo = "saida" THEN quantidade ELSE 0 END) as saidas,
                SUM(CASE WHEN tipo = "entrada" THEN COALESCE(valor_total, 0) ELSE 0 END) as valor_entradas')
            ->groupBy('produto_id')
            ->get();

        $produtoIds = collect($iniciais->keys())
            ->merge($periodoRows->pluck('produto_id'))
            ->unique()
            ->values();

        $produtos = Produto::where('empresa_id', $this->empresa_id)
            ->whereIn('id', $produtoIds)
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->keyBy('id');

        $resumo = [];
        foreach ($produtoIds as $produtoId) {
            $linhaPeriodo = $periodoRows->firstWhere('produto_id', (int) $produtoId);
            $saldoInicial = (float) ($iniciais[$produtoId] ?? 0);
            $entradas = (float) ($linhaPeriodo->entradas ?? 0);
            $saidas = (float) ($linhaPeriodo->saidas ?? 0);
            $saldoFinal = $saldoInicial + $entradas - $saidas;
            $valorEntradas = (float) ($linhaPeriodo->valor_entradas ?? 0);
            $custoMedio = $entradas > 0 ? ($valorEntradas / $entradas) : 0;

            $resumo[] = [
                'produto_id' => (int) $produtoId,
                'produto_nome' => $produtos[$produtoId]->nome ?? ('Produto #' . $produtoId),
                'saldo_inicial' => $saldoInicial,
                'entradas' => $entradas,
                'saidas' => $saidas,
                'saldo_final' => $saldoFinal,
                'custo_medio' => $custoMedio,
                'valor_total_estimado' => $saldoFinal * $custoMedio,
            ];
        }

        usort($resumo, fn ($a, $b) => strcmp($a['produto_nome'], $b['produto_nome']));

        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get();

        $produtosFiltro = Produto::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('estoque.kardex.index', [
            'title' => 'Kardex (Ledger) - Resumo por Produto',
            'resumo' => $resumo,
            'filiais' => $filiais,
            'produtosFiltro' => $produtosFiltro,
            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'contexto' => $contexto,
                'filial_id' => $filialFiltro,
                'produto_id' => $produtoIdFiltro,
            ],
        ]);
    }

    public function show(Request $request, Produto $produto)
    {
        if ((int) $produto->empresa_id !== (int) $this->empresa_id) {
            abort(404);
        }

        $dataInicio = $request->input('data_inicio', now()->startOfMonth()->toDateString());
        $dataFim = $request->input('data_fim', now()->toDateString());
        $contexto = strtoupper($request->input('contexto', 'TODOS'));

        $filialFiltro = $request->filled('filial_id')
            ? (int) $request->input('filial_id')
            : $this->filial_id;

        $base = StockMovement::query()
            ->where('empresa_id', $this->empresa_id)
            ->where('produto_id', $produto->id);

        if (!empty($filialFiltro)) {
            $base->where('filial_id', $filialFiltro);
        }
        if ($contexto !== 'TODOS') {
            $base->where('contexto', $contexto);
        }

        $saldoInicial = (float) (clone $base)
            ->where('movimentado_em', '<', $dataInicio . ' 00:00:00')
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = "entrada" THEN quantidade ELSE -quantidade END), 0) as saldo')
            ->value('saldo');

        $perPage = 50;
        $page = max(1, (int) $request->input('page', 1));

        $movimentosQuery = (clone $base)
            ->where('movimentado_em', '>=', $dataInicio . ' 00:00:00')
            ->where('movimentado_em', '<=', $dataFim . ' 23:59:59')
            ->orderBy('movimentado_em')
            ->orderBy('id');

        $movimentos = $movimentosQuery->paginate($perPage)->appends($request->query());

        $qtdSkip = ($page - 1) * $perPage;
        $saldoAntesPagina = $saldoInicial;
        if ($qtdSkip > 0) {
            $anteriores = (clone $movimentosQuery)
                ->limit($qtdSkip)
                ->get(['tipo', 'quantidade']);

            foreach ($anteriores as $movAnterior) {
                $saldoAntesPagina += $movAnterior->tipo === 'entrada'
                    ? (float) $movAnterior->quantidade
                    : -1 * (float) $movAnterior->quantidade;
            }
        }

        $saldoProgressivo = $saldoAntesPagina;
        foreach ($movimentos as $mov) {
            $saldoProgressivo += $mov->tipo === 'entrada'
                ? (float) $mov->quantidade
                : -1 * (float) $mov->quantidade;
            $mov->saldo_progressivo = $saldoProgressivo;
        }

        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get();

        return view('estoque.kardex.show', [
            'title' => 'Kardex Analítico - ' . $produto->nome,
            'produto' => $produto,
            'movimentos' => $movimentos,
            'saldoInicial' => $saldoInicial,
            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'contexto' => $contexto,
                'filial_id' => $filialFiltro,
            ],
            'filiais' => $filiais,
        ]);
    }
}
