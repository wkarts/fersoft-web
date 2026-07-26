<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\Fornecedor;
use App\Models\CategoriaConta;
use App\Models\Filial;

class CompraConferenciaController extends BaseController
{
    // Propriedade obrigatória pelo BaseController
    protected $redirectPage = '/compraconferencia';

    public function index(Request $request)
    {
        $data_inicial = $request->input('data_inicial', date('Y-m-01'));
        $data_final = $request->input('data_final', date('Y-m-d'));
        $fornecedor_id = $request->input('fornecedor_id');
        $numero_nota = $request->input('numero_nota');
        $estado_filtro = $request->input('estado');
        $filial_id = $request->input('filial_id', 'todos');
        $categoria_id = array_values(array_filter((array) $request->input('categoria_id', [])));

        $query = Compra::with(['fornecedor', 'contasPagar.categoria', 'itens'])
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_emissao', [$data_inicial, $data_final]);

        if ($numero_nota) {
            $query->where(function ($q) use ($numero_nota) {
                $q->where('nf', $numero_nota)
                    ->orWhere('numero_emissao', $numero_nota);
            });
        }

        if ($fornecedor_id && $fornecedor_id !== 'todos') {
            $query->where('fornecedor_id', $fornecedor_id);
        }

        if ($filial_id && $filial_id !== 'todos') {
            $filial_id === 'matriz'
                ? $query->whereNull('filial_id')
                : $query->where('filial_id', (int) $filial_id);
        }

        if ($categoria_id !== []) {
            $query->whereHas('contasPagar', function ($q) use ($categoria_id) {
                $q->whereIn('categoria_id', $categoria_id);
            });
        }

        if ($estado_filtro && $estado_filtro !== 'todos') {
            $query->where('estado', $estado_filtro);
        }

        $compras = $query->orderByDesc('data_emissao')->get();

        $total_propria = 0;
        $total_terceiro = 0;
        $soma_valores = 0.0;
        $total_produtos = 0.0;

        foreach ($compras as $compra) {
            $estado = strtoupper((string) $compra->estado);

            if ($estado === 'IMPORTADO') {
                $compra->tipo_relatorio = 'TERCEIRO';
                $compra->numero_exibicao = $compra->nf;
                $total_terceiro++;
            } else {
                $compra->tipo_relatorio = 'PRÓPRIA';
                $compra->numero_exibicao = $compra->numero_emissao;
                $total_propria++;
            }

            $soma_valores += (float) ($compra->valor ?? 0);
            $total_produtos += (float) $compra->itens->sum('quantidade');
        }

        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)
            ->orderBy('razao_social')
            ->get();
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->orderBy('nome')
            ->get();
        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get();
        $title = 'Conferência de Compras';

        return view('compra_conferencia.index', compact(
            'compras', 'fornecedores', 'categorias', 'filiais', 'total_propria',
            'total_terceiro', 'soma_valores', 'total_produtos', 'data_inicial',
            'data_final', 'filial_id', 'categoria_id', 'title'
        ));
    }

    // Métodos abstratos obrigatórios
    public function rules(): array { return []; }
    public function messages(): array { return []; }
}