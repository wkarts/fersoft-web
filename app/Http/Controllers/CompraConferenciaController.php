<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\Fornecedor;
use App\Models\CategoriaConta;

class CompraConferenciaController extends BaseController
{
    // Propriedade obrigatória pelo BaseController
    protected $redirectPage = '/compraconferencia';

    public function index(Request $request)
    {
        $data_inicial = $request->data_inicial ?? date('Y-m-01');
        $data_final = $request->data_final ?? date('Y-m-d');
        $fornecedor_id = $request->fornecedor_id;
        $numero_nota = $request->numero_nota;
        $categoria_id = $request->categoria_id;
        $estado_filtro = $request->estado;

        // Consulta com relacionamentos
        $query = Compra::with(['fornecedor', 'contasPagar.categoria', 'itens'])
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_emissao', [$data_inicial, $data_final]);

        // Aplicação de Filtros
        if ($numero_nota) {
            $query->where(function($q) use ($numero_nota) {
                $q->where('nf', $numero_nota)->orWhere('numero_emissao', $numero_nota);
            });
        }

        if ($fornecedor_id && $fornecedor_id != 'todos') {
            $query->where('fornecedor_id', $fornecedor_id);
        }

        if ($categoria_id && $categoria_id != 'todos') {
            $query->whereHas('contasPagar', function($q) use ($categoria_id) {
                $q->where('categoria_id', $categoria_id);
            });
        }

        if ($estado_filtro && $estado_filtro != 'todos') {
            $query->where('estado', $estado_filtro);
        }

        $compras = $query->orderBy('data_emissao', 'desc')->get();

        // Variáveis de Totalização
        $total_propria = 0;
        $total_terceiro = 0;
        $soma_valores = 0;
        $total_produtos = 0;

        foreach ($compras as $c) {
            $estado_upper = strtoupper($c->estado);
            
            // Lógica de Identificação e Coluna da Nota
            if ($estado_upper == 'IMPORTADO') {
                $c->tipo_relatorio = 'TERCEIRO';
                $c->numero_exibicao = $c->nf;
                $total_terceiro++;
            } else {
                $c->tipo_relatorio = 'PRÓPRIA';
                $c->numero_exibicao = $c->numero_emissao;
                $total_propria++;
            }
            
            $soma_valores += (float)($c->valor ?? 0);
            $total_produtos += $c->itens->sum('quantidade');
        }

        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get();
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->get();
        $title = "Conferência de Compras";

        return view('compra_conferencia.index', compact(
            'compras', 'fornecedores', 'categorias', 'total_propria', 'total_terceiro', 
            'soma_valores', 'total_produtos', 'data_inicial', 'data_final', 'title'
        ));
    }

    // Métodos abstratos obrigatórios
    public function rules(): array { return []; }
    public function messages(): array { return []; }
}