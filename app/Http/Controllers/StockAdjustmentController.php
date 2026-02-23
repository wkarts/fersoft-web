<?php

namespace App\Http\Controllers;

use App\Models\Filial;
use App\Models\Produto;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockAdjustmentController extends BaseController
{
    protected $redirectPage = '/estoque/ajustes';


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
        $dataInicio = $request->input('data_inicio', now()->toDateString());
        $dataFim = $request->input('data_fim', now()->toDateString());

        $filialFiltro = $request->filled('filial_id')
            ? (int) $request->input('filial_id')
            : $this->filial_id;

        $query = StockAdjustment::where('empresa_id', $this->empresa_id)
            ->whereDate('data_ref', '>=', $dataInicio)
            ->whereDate('data_ref', '<=', $dataFim)
            ->orderByDesc('data_ref')
            ->orderByDesc('id');

        if (!empty($filialFiltro)) {
            $query->where('filial_id', $filialFiltro);
        }

        $ajustes = $query->paginate(20)->appends($request->query());

        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get();

        return view('estoque.ajustes.index', [
            'title' => 'Ajustes de Estoque (Ledger)',
            'ajustes' => $ajustes,
            'filiais' => $filiais,
            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'filial_id' => $filialFiltro,
            ],
            'links' => true,
        ]);
    }

    public function create(Request $request)
    {
        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get();

        $produtos = Produto::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->orderBy('nome')
            ->get(['id', 'nome', 'unidade_venda']);

        return view('estoque.ajustes.create', [
            'title' => 'Novo Ajuste de Estoque',
            'filiais' => $filiais,
            'produtos' => $produtos,
            'filialPadrao' => $this->filial_id,
            'dataRefPadrao' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, StockService $stockService)
    {
        $request->validate([
            'data_ref' => 'required|date',
            'filial_id' => 'nullable|integer',
            'observacao' => 'nullable|string|max:2000',
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'required|integer',
            'itens.*.tipo' => 'required|in:entrada,saida',
            'itens.*.quantidade' => 'required|numeric|gt:0',
            'itens.*.custo_unitario' => 'nullable|numeric|min:0',
        ]);

        $itensNormalizados = [];
        foreach ($request->itens as $idx => $item) {
            $produto = Produto::where('empresa_id', $this->empresa_id)
                ->where('id', (int) $item['produto_id'])
                ->first();

            if (!$produto) {
                continue;
            }

            $itensNormalizados[] = [
                'produto_id' => (int) $produto->id,
                'tipo' => $item['tipo'],
                'quantidade' => (float) $item['quantidade'],
                'custo_unitario' => $item['custo_unitario'] !== null && $item['custo_unitario'] !== ''
                    ? (float) $item['custo_unitario']
                    : null,
                'idx' => $idx,
            ];
        }

        if (count($itensNormalizados) === 0) {
            return redirect()->back()->withInput()->withErrors([
                'itens' => 'Informe ao menos um item válido para ajuste.',
            ]);
        }

        $ajuste = StockAdjustment::create([
            'empresa_id' => $this->empresa_id,
            'filial_id' => $request->filled('filial_id') ? (int) $request->filial_id : $this->filial_id,
            'usuario_id' => $this->usuario_id,
            'data_ref' => $request->data_ref,
            'observacao' => $request->observacao,
            'itens' => $itensNormalizados,
        ]);

        foreach ($itensNormalizados as $item) {
            $idempotencyKey = sprintf(
                'stock_adjustment:%d:idx:%d:produto:%d:tipo:%s',
                $ajuste->id,
                $item['idx'],
                $item['produto_id'],
                $item['tipo']
            );

            $stockService->mover([
                'empresa_id' => $this->empresa_id,
                'filial_id' => $ajuste->filial_id,
                'usuario_id' => $this->usuario_id,
                'produto_id' => $item['produto_id'],
                'contexto' => 'ERP',
                'tipo' => $item['tipo'],
                'quantidade' => $item['quantidade'],
                'custo_unitario' => $item['custo_unitario'],
                'origem_tipo' => 'stock_adjustment',
                'origem_id' => $ajuste->id,
                'idempotency_key' => $idempotencyKey,
                'movimentado_em' => now(),
                'metadata' => [
                    'stock_adjustment_id' => $ajuste->id,
                    'idx' => $item['idx'],
                    'observacao' => $ajuste->observacao,
                ],
            ]);
        }

        session()->flash('mensagem_sucesso', 'Ajuste de estoque registrado com sucesso.');

        return redirect('/estoque/ajustes/' . $ajuste->id);
    }

    public function show($id)
    {
        $ajuste = StockAdjustment::where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        $produtoIds = collect($ajuste->itens ?? [])->pluck('produto_id')->filter()->unique()->values();

        $produtos = Produto::where('empresa_id', $this->empresa_id)
            ->whereIn('id', $produtoIds)
            ->get(['id', 'nome'])
            ->keyBy('id');

        $movimentos = StockMovement::where('empresa_id', $this->empresa_id)
            ->where('origem_tipo', 'stock_adjustment')
            ->where('origem_id', $ajuste->id)
            ->orderBy('movimentado_em')
            ->orderBy('id')
            ->get();

        return view('estoque.ajustes.show', [
            'title' => 'Detalhe Ajuste #' . $ajuste->id,
            'ajuste' => $ajuste,
            'produtos' => $produtos,
            'movimentos' => $movimentos,
        ]);
    }
}
