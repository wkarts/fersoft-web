<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ConfigNota;
use App\Models\NaturezaOperacao;
use App\Models\Produto;
use App\Models\ReceitaOtica;
use App\Models\Venda;
use App\Models\ItemVenda;
use App\Models\VendaCaixaPreVenda;
use App\Models\ItemVendaCaixaPreVenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OticaController extends BaseController
{
    protected $model = ReceitaOtica::class;
    protected $redirectPage = '/otica';
    protected $formTitle = 'Ordem de Serviço - Ótica';
    protected $resource = 'otica';
    protected $listView = 'otica.list';
    protected $registerView = 'otica.register';
    protected $Prefix_Route = 'otica';

    protected function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer'],
            'status' => ['required', 'string', 'max:40'],
        ];
    }

    protected function messages(): array
    {
        return [
            'cliente_id.required' => 'Selecione um cliente.',
            'status.required' => 'Defina o status da OS.',
        ];
    }

    public function list(Request $request)
    {
        $empresaId = $this->empresa_id;

        $query = ReceitaOtica::with('cliente')
            ->where('empresa_id', $empresaId)
            ->when($request->filled('cliente'), function ($q) use ($request) {
                $search = trim((string) $request->cliente);
                $q->whereHas('cliente', function ($clienteQuery) use ($search) {
                    $clienteQuery->where('razao_social', 'like', "%{$search}%")
                        ->orWhere('nome_fantasia', 'like', "%{$search}%")
                        ->orWhere('cpf_cnpj', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id');

        $data = $query->paginate(20)->appends($request->except('page'));
        $title = $this->formTitle;
        $cliente = $request->cliente;
        $status = $request->status;

        return view($this->listView, compact('data', 'title', 'cliente', 'status'));
    }

    public function register($id = null)
    {
        $item = $id ? ReceitaOtica::where('empresa_id', $this->empresa_id)->findOrFail($id) : null;
        $title = ($id ? 'Editar ' : 'Nova ') . $this->formTitle;
        $formTitle = $this->formTitle;
        $data = collect([]);

        return view($this->registerView, compact('item', 'title', 'formTitle', 'data'));
    }

    public function store(Request $request)
    {
        return $this->salvarOrdemDeServico($request);
    }

    public function save(Request $request)
    {
        return $this->salvarOrdemDeServico($request);
    }

    public function update(Request $request, $id)
    {
        return $this->salvarOrdemDeServico($request, $id);
    }

    private function salvarOrdemDeServico(Request $request, $id = null)
    {
        $request->validate($this->rules(), $this->messages());

        try {
            $id = $id ?: $request->id;
            $dados = $request->except(['_token', '_method', 'total_os']);
            $dados['empresa_id'] = $this->empresa_id;
            $dados['filial_id'] = $this->filial_id;

            foreach (['valor_armacao', 'valor_lente'] as $campo) {
                if (isset($dados[$campo]) && $dados[$campo] !== '') {
                    $dados[$campo] = str_replace(['.', ','], ['', '.'], (string) $dados[$campo]);
                }
            }

            if (!empty($dados['armacao_id'])) {
                $produto = Produto::where('empresa_id', $this->empresa_id)->find($dados['armacao_id']);
                if ($produto) {
                    $dados['armacao'] = $produto->nome;
                }
            }

            if (!empty($dados['lente_id'])) {
                $produto = Produto::where('empresa_id', $this->empresa_id)->find($dados['lente_id']);
                if ($produto) {
                    $dados['lente'] = $produto->nome;
                }
            }

            if (!empty($dados['previsao_retorno_dias'])) {
                $dados['data_entrega'] = now()->addDays((int) $dados['previsao_retorno_dias'])->format('Y-m-d');
            }

            if ($id) {
                $os = ReceitaOtica::where('empresa_id', $this->empresa_id)->findOrFail($id);
                $os->update($dados);
                $msg = 'Ordem de Serviço atualizada com sucesso!';
            } else {
                $dados['data'] = $dados['data'] ?? now()->format('Y-m-d');
                ReceitaOtica::create($dados);
                $msg = 'Ordem de Serviço cadastrada com sucesso!';
            }

            return redirect()->route('otica.index')->with('success', $msg);
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar OS de Ótica', [
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'exception' => $e->getMessage(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Erro ao salvar OS de Ótica: ' . $e->getMessage());
        }
    }

    public function imprimirOS($id)
    {
        $os = ReceitaOtica::with(['cliente'])->where('empresa_id', $this->empresa_id)->findOrFail($id);
        $config = DB::table('empresas')->where('id', $this->empresa_id)->first();
        return view('otica.print_os', compact('os', 'config'));
    }

    public function imprimirRecibo($id)
    {
        $os = ReceitaOtica::with(['cliente'])->where('empresa_id', $this->empresa_id)->findOrFail($id);
        $config = DB::table('empresas')->where('id', $this->empresa_id)->first();
        return view('otica.print_recibo', compact('os', 'config'));
    }

    public function alterarStatus(Request $request)
    {
        try {
            $request->validate(['id' => ['required', 'integer'], 'status' => ['required', 'string', 'max:40']]);
            $os = ReceitaOtica::where('empresa_id', $this->empresa_id)->findOrFail($request->id);
            $os->status = $request->status;
            $os->save();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function faturar(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $os = ReceitaOtica::where('empresa_id', $this->empresa_id)->findOrFail($id);

            if ($os->status === 'entregue' || $os->venda_id) {
                return redirect()->back()->with('error', 'Esta OS já possui uma venda vinculada.');
            }

            $naturezaId = optional(ConfigNota::where('empresa_id', $this->empresa_id)->first())->nat_op_padrao;
            if (!$naturezaId) {
                $naturezaId = optional(NaturezaOperacao::where('empresa_id', $this->empresa_id)->first())->id;
            }

            if (!$naturezaId) {
                return redirect()->back()->with('error', 'Configuração de Natureza de Operação não encontrada.');
            }

            $tipo = $request->get('tipo', 'nfe');
            $valorTotal = (float) $os->valor_lente + (float) $os->valor_armacao;

            if ($tipo === 'pdv') {
                $preVenda = VendaCaixaPreVenda::create([
                    'empresa_id' => $this->empresa_id,
                    'filial_id' => $this->filial_id,
                    'usuario_id' => $this->usuario_id,
                    'cliente_id' => $os->cliente_id,
                    'natureza_id' => $naturezaId,
                    'valor_total' => $valorTotal,
                    'estado' => 'DISPONIVEL',
                    'prevenda_nivel' => 2,
                    'observacao' => 'Origem OS Ótica #' . $os->id,
                ]);

                if ($os->lente_id && $os->valor_lente > 0) {
                    ItemVendaCaixaPreVenda::create([
                        'venda_caixa_prevenda_id' => $preVenda->id,
                        'produto_id' => $os->lente_id,
                        'quantidade' => $os->qtd_lente ?: 1,
                        'valor' => $os->valor_lente / ($os->qtd_lente ?: 1),
                    ]);
                }

                if ($os->armacao_id && $os->valor_armacao > 0) {
                    ItemVendaCaixaPreVenda::create([
                        'venda_caixa_prevenda_id' => $preVenda->id,
                        'produto_id' => $os->armacao_id,
                        'quantidade' => $os->qtd_armacao ?: 1,
                        'valor' => $os->valor_armacao / ($os->qtd_armacao ?: 1),
                    ]);
                }

                $os->update(['status' => 'entregue', 'venda_id' => $preVenda->id]);
                return redirect('/frenteCaixa')->with('success', 'Pré-venda gerada com sucesso!');
            }

            $venda = Venda::create([
                'empresa_id' => $this->empresa_id,
                'filial_id' => $this->filial_id,
                'usuario_id' => $this->usuario_id,
                'cliente_id' => $os->cliente_id,
                'natureza_id' => $naturezaId,
                'valor_total' => $valorTotal,
                'estado' => 'DISPONIVEL',
                'observacao' => 'Origem OS Ótica #' . $os->id,
            ]);

            if ($os->lente_id && $os->valor_lente > 0) {
                ItemVenda::create([
                    'empresa_id' => $this->empresa_id,
                    'venda_id' => $venda->id,
                    'produto_id' => $os->lente_id,
                    'quantidade' => $os->qtd_lente ?: 1,
                    'valor' => $os->valor_lente / ($os->qtd_lente ?: 1),
                ]);
            }

            if ($os->armacao_id && $os->valor_armacao > 0) {
                ItemVenda::create([
                    'empresa_id' => $this->empresa_id,
                    'venda_id' => $venda->id,
                    'produto_id' => $os->armacao_id,
                    'quantidade' => $os->qtd_armacao ?: 1,
                    'valor' => $os->valor_armacao / ($os->qtd_armacao ?: 1),
                ]);
            }

            $os->update(['status' => 'entregue', 'venda_id' => $venda->id]);
            return redirect('/vendas/edit/' . $venda->id)->with('success', 'Venda gerada para NF-e!');
        });
    }

    public function buscarClientes(Request $request)
    {
        $search = trim((string) $request->q);
        $data = Cliente::where('empresa_id', $this->empresa_id)
            ->where(function ($q) use ($search) {
                $q->where('razao_social', 'like', "%{$search}%")
                    ->orWhere('nome_fantasia', 'like', "%{$search}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        return response()->json($data->map(fn ($item) => [
            'id' => $item->id,
            'text' => $item->razao_social . ' (' . $item->cpf_cnpj . ')',
        ]));
    }

    public function buscarProdutos(Request $request)
    {
        $search = trim((string) $request->q);
        $data = Produto::where('empresa_id', $this->empresa_id)
            ->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('referencia', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            })
            ->limit(20)
            ->get();

        return response()->json($data->map(fn ($item) => [
            'id' => $item->id,
            'text' => ($item->referencia ? $item->referencia . ' - ' : '') . $item->nome,
            'preco' => $item->valor_venda,
        ]));
    }

    public function clienteRapido(Request $request)
    {
        try {
            $cliente = Cliente::create([
                'empresa_id' => $this->empresa_id,
                'razao_social' => $request->razao_social,
                'nome_fantasia' => $request->razao_social,
                'cpf_cnpj' => $request->cpf_cnpj ?: '000.000.000-00',
                'telefone' => $request->telefone,
                'data_nascimento' => $request->data_nascimento,
                'rua' => $request->rua ?: '',
                'bairro' => $request->bairro ?: '',
                'numero' => $request->numero ?: 'S/N',
                'cidade_id' => $request->cidade_id ?: 1,
                'consumidor_final' => 1,
                'contribuinte' => 0,
                'inativo' => 0,
            ]);

            return response()->json(['success' => true, 'id' => $cliente->id, 'nome' => $cliente->razao_social]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function delete($id)
    {
        return $this->removerOS($id);
    }

    public function destroy($id)
    {
        return $this->removerOS($id);
    }

    private function removerOS($id)
    {
        try {
            $os = ReceitaOtica::where('empresa_id', $this->empresa_id)->find($id);
            if (!$os) {
                return redirect()->route('otica.index');
            }

            if ($os->venda_id || $os->status === 'entregue') {
                return redirect()->route('otica.index')->with('error', 'Ação bloqueada: não pode excluir uma OS já faturada/entregue.');
            }

            $os->delete();
            return redirect()->route('otica.index')->with('success', 'Ordem de Serviço excluída com sucesso!');
        } catch (\Throwable $e) {
            return redirect()->route('otica.index')->with('error', 'Erro ao excluir: ' . $e->getMessage());
        }
    }
}
