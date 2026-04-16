<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Adiantamento;
use App\Models\ItemContaEmpresa;
use App\Models\ContaEmpresa;
use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\CategoriaConta;
use Illuminate\Support\Facades\DB;

// Importante: Estender BaseController para herdar empresa_id e filtros de Tenant
class AdiantamentoController extends BaseController
{
    protected $model = Adiantamento::class;
    
    protected $resource = 'adiantamentos';
    protected $table = 'adiantamentos';
    protected $formTitle = 'Controle de Adiantamentos';
    protected $redirectPage = '/adiantamentos';

    public function rules(): array {
        return [
            'tipo_pessoa' => 'required',
            'pessoa_id' => 'required',
            'valor' => 'required',
            'data' => 'required',
            'conta_id' => 'required',
            'categoria_id' => 'required'
        ];
    }

    public function messages(): array {
        return [
            'tipo_pessoa.required' => 'Selecione se é Cliente ou Fornecedor.',
            'pessoa_id.required' => 'Busque e selecione a Razão Social.',
            'valor.required' => 'O valor do adiantamento é obrigatório.',
            'data.required' => 'A data é obrigatória.',
            'conta_id.required' => 'Selecione a conta bancária.',
            'categoria_id.required' => 'Selecione a categoria financeira.'
        ];
    }
  
    public function index(Request $request)
    {
        __saveRedirect($this->empresa_id, '', 'adiantamentos');

        $saldos = Adiantamento::where('empresa_id', $this->empresa_id)
            ->where('status', '!=', 'cancelado')
            ->select(
                'cliente_id', 
                'fornecedor_id', 
                DB::raw('SUM(valor_total) as total_gerado'),
                DB::raw('SUM(valor_utilizado) as total_usado'),
                DB::raw('SUM(valor_total - valor_utilizado) as saldo_disponivel')
            )
            ->groupBy('cliente_id', 'fornecedor_id')
            ->with(['cliente', 'fornecedor'])
            ->orderByDesc('saldo_disponivel') 
            ->get();

        $contas = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->get();
        $categorias = \App\Models\CategoriaConta::where('empresa_id', $this->empresa_id)->get();

        return view('adiantamentos.index', compact('saldos', 'contas', 'categorias'));
    }  

    public function buscarPessoas(Request $request)
    {
        $pesquisa = $request->term;
        $tipo = $request->tipo;

        $query = ($tipo == 'cliente') ? Cliente::query() : Fornecedor::query();

        $data = $query->where('empresa_id', $this->empresa_id)
            ->where(function($q) use ($pesquisa) {
                $q->where('razao_social', 'LIKE', "%$pesquisa%")
                  ->orWhere('nome_fantasia', 'LIKE', "%$pesquisa%")
                  ->orWhere('cpf_cnpj', 'LIKE', "%$pesquisa%");
            })
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $data->map(function($item) {
                $identificador = $item->cpf_cnpj ? $item->cpf_cnpj . " - " : "";
                $nomeExibicao = $item->nome_fantasia ?: $item->razao_social;

                return [
                    'id' => $item->id,
                    'text' => $identificador . $nomeExibicao
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $valor = str_replace(['.', ','], ['', '.'], $request->valor);
                $filial_id = __get_local_padrao() == -1 ? null : __get_local_padrao();

                $conta = ContaEmpresa::findOrFail($request->conta_id);
                if ($request->tipo_pessoa == 'cliente') {
                    $conta->saldo += $valor;
                    $descricao = "Adiant. Cliente: " . $request->nome_pessoa;
                    $tipo_mov = 'entrada';
                } else {
                    $conta->saldo -= $valor;
                    $descricao = "Adiant. Fornecedor: " . $request->nome_pessoa;
                    $tipo_mov = 'saida';
                }
                $conta->save();

                $item = ItemContaEmpresa::create([
                    'conta_id' => $conta->id,
                    'descricao' => $descricao,
                    'data_pagamento' => $request->data,
                    'valor' => $valor,
                    'tipo' => $tipo_mov,
                    'categoria_id' => $request->categoria_id,
                    'saldo_atual' => $conta->saldo,
                    'empresa_id' => $this->empresa_id,
                    'user_id' => auth()->user()->id ?? 1
                ]);

                Adiantamento::create([
                    'empresa_id' => $this->empresa_id,
                    'filial_id' => $filial_id,
                    'cliente_id' => ($request->tipo_pessoa == 'cliente') ? $request->pessoa_id : null,
                    'fornecedor_id' => ($request->tipo_pessoa == 'fornecedor') ? $request->pessoa_id : null,
                    'valor_total' => $valor,
                    'valor_utilizado' => 0,
                    'data' => $request->data,
                    'descricao' => $descricao,
                    'item_conta_empresa_id' => $item->id,
                    'status' => 'aberto'
                ]);

                return redirect()->back()->with('success', 'Adiantamento registrado com sucesso!');
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao salvar: ' . $e->getMessage());
        }
    }

    public function cancelar($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $adv = Adiantamento::findOrFail($id);

                if ($adv->valor_utilizado > 0) {
                    return redirect()->back()->with('error', 'Este adiantamento já possui utilizações e não pode ser estornado.');
                }

                $itemOrig = ItemContaEmpresa::find($adv->item_conta_empresa_id);
                $conta = ContaEmpresa::find($itemOrig->conta_id);

                if ($adv->cliente_id) {
                    $conta->saldo -= $adv->valor_total;
                    $tipo_estorno = 'saida';
                } else {
                    $conta->saldo += $adv->valor_total;
                    $tipo_estorno = 'entrada';
                }
                $conta->save();

                ItemContaEmpresa::create([
                    'conta_id' => $conta->id,
                    'descricao' => "ESTORNO: " . $adv->descricao,
                    'data_pagamento' => date('Y-m-d'),
                    'valor' => $adv->valor_total,
                    'tipo' => $tipo_estorno,
                    'categoria_id' => $itemOrig->categoria_id,
                    'saldo_atual' => $conta->saldo,
                    'empresa_id' => $this->empresa_id,
                    'user_id' => auth()->user()->id ?? 1
                ]);

                $adv->update(['status' => 'cancelado']);

                return redirect()->back()->with('success', 'Adiantamento estornado com sucesso!');
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao estornar: ' . $e->getMessage());
        }
    }
 
    public function extrato($tipo, $id)
    {
        $pessoa = ($tipo == 'cliente') 
            ? \App\Models\Cliente::findOrFail($id) 
            : \App\Models\Fornecedor::findOrFail($id);

        $lancamentos = Adiantamento::with('movimentacoes')
            ->where('empresa_id', $this->empresa_id)
            ->where($tipo . '_id', $id)
            ->orderBy('data', 'desc')
            ->get();

        return view('adiantamentos.extrato', compact('pessoa', 'lancamentos', 'tipo'));
    }
  
    public function getSaldoPessoa($tipo, $id)
    {
        try {
            $adiantamentos = \App\Models\Adiantamento::where('empresa_id', $this->empresa_id)
                ->where($tipo . '_id', $id)
                ->where('status', '!=', 'cancelado');
                
            $totalCredito = $adiantamentos->sum('valor_total');
            $idsAdiantamentos = $adiantamentos->pluck('id');

            $totalUsado = 0;
            if (count($idsAdiantamentos) > 0) {
                $totalUsado = \App\Models\AdiantamentoMovimentacao::whereIn('adiantamento_id', $idsAdiantamentos)
                    ->sum('valor');
            }

            $saldoFinal = $totalCredito - $totalUsado;

            return response()->json(['saldo' => $saldoFinal > 0 ? $saldoFinal : 0]);

        } catch (\Exception $e) {
            return response()->json(['saldo' => 0, 'erro' => $e->getMessage()]);
        }
    }
  
    public function sincronizar()
    {
        return redirect()->back()->with('success', 'Sincronização iniciada! O sistema está a procurar notas autorizadas.');
    }

    /**
     * Método Estático ÚNICO para uso em outros módulos (Vendas/Compras)
     * Adicionamos "= null" no id_documento para que ele funcione na sua Compra Manual sem causar erro!
     */
    public static function baixarAdiantamento($pessoa_id, $tipo, $valor_nota, $empresa_id, $id_documento = null)
    {
        $adiantamentos = Adiantamento::where('empresa_id', $empresa_id)
            ->where($tipo . '_id', $pessoa_id)
            ->where('status', 'aberto')
            ->orderBy('data', 'asc')
            ->get();

        $saldo_abater = $valor_nota;

        foreach ($adiantamentos as $adv) {
            if ($saldo_abater <= 0) break;

            $disponivel = $adv->valor_total - $adv->valor_utilizado;
            $usar = ($disponivel > $saldo_abater) ? $saldo_abater : $disponivel;

            $adv->valor_utilizado += $usar;
            if ($adv->valor_utilizado >= $adv->valor_total) {
                $adv->status = 'finalizado';
            }
            $adv->save();

            DB::table('adiantamento_movimentacoes')->insert([
                'adiantamento_id' => $adv->id,
                'conta_receber_id' => ($tipo == 'cliente') ? $id_documento : null,
                'conta_pagar_id' => ($tipo == 'fornecedor') ? $id_documento : null,
                'valor' => $usar,
                'data' => date('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $saldo_abater -= $usar;
        }
    }
}