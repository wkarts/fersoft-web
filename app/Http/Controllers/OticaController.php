<?php

namespace App\Http\Controllers;

use App\Models\ReceitaOtica;
use App\Models\Produto;
use App\Models\Cliente;
use App\Models\Venda;
use App\Models\ItemVenda;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class OticaController extends BaseController
{
    public function __construct()
    {
        $this->model = new ReceitaOtica();
        $this->formTitle    = 'Ordem de Serviço - Ótica';
        $this->Prefix_Route = 'otica';
        $this->listView     = 'otica.list';
        $this->registerView = 'otica.register';
    }

    public function rules(): array {
        return ['cliente_id' => 'required', 'status' => 'required'];
    }

    public function messages(): array {
        return ['cliente_id.required' => 'Selecione um cliente.', 'status.required' => 'Defina o status da OS.'];
    }

    // --- LISTAGEM ---
    public function list(Request $request)
    {
        $empresa_id = session('user_logged')['empresa'] ?? 1;

        $data = ReceitaOtica::where('empresa_id', $empresa_id)
            ->orderBy('id', 'desc')
            ->paginate(20);

        $title = $this->formTitle;
        return view($this->listView, compact('data', 'title'));
    }

    // --- TELA DE CADASTRO/EDIÇÃO ---
    public function register($id = null)
    {
        $item = $id ? ReceitaOtica::findOrFail($id) : null;
        $title = ($id ? "Editar " : "Nova ") . $this->formTitle;
        $formTitle = $this->formTitle;
        $data = collect([]);
        return view($this->registerView, compact('item', 'title', 'formTitle', 'data'));
    }

    // --- SALVAMENTO E ATUALIZAÇÃO BLINDADOS ---

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
        try {
            $id = $id ?? $request->id;
            $os = $id ? \App\Models\ReceitaOtica::find($id) : null;

            // Proteção contra alteração pós-venda
            if ($os && ($os->status == 'entregue' || $os->venda_id)) {
                return redirect()->route('otica.index')->with('error', 'Esta OS está faturada e não pode receber alterações.');
            }

            $dados = $request->except(['_token', '_method', 'total_os', 'anexo_receita']);
            $dados['empresa_id'] = session('user_logged')['empresa'] ?? 1;

            // Upload do PDF/Foto da Receita
            if ($request->hasFile('anexo_receita') && $request->file('anexo_receita')->isValid()) {
                // Remove o anexo antigo se existir
                if ($os && $os->anexo_receita) {
                    Storage::disk('public')->delete($os->anexo_receita);
                }
                $path = $request->file('anexo_receita')->store('receitas_otica', 'public');
                $dados['anexo_receita'] = $path;
            }

            // Tratamento de valores decimais
            if (!empty($dados['valor_armacao'])) $dados['valor_armacao'] = str_replace(['.', ','], ['', '.'], $dados['valor_armacao']);
            if (!empty($dados['valor_lente'])) $dados['valor_lente'] = str_replace(['.', ','], ['', '.'], $dados['valor_lente']);

            if (!empty($dados['armacao_id'])) {
                $prod = \App\Models\Produto::find($dados['armacao_id']);
                if ($prod) $dados['armacao'] = $prod->nome;
            }
            if (!empty($dados['lente_id'])) {
                $prod = \App\Models\Produto::find($dados['lente_id']);
                if ($prod) $dados['lente'] = $prod->nome;
            }

            if (!empty($dados['previsao_retorno_dias'])) {
                $dados['data_entrega'] = date('Y-m-d', strtotime('+' . $dados['previsao_retorno_dias'] . ' days'));
            }

            if ($id) {
                $os->update($dados);
                $msg = 'Ordem de Serviço atualizada com sucesso!';
            } else {
                $dados['data'] = date('Y-m-d');
                \App\Models\ReceitaOtica::create($dados);
                $msg = 'Ordem de Serviço cadastrada com sucesso!';
            }

            return redirect()->route('otica.index')->with('success', $msg);

        } catch (\Exception $e) {
            dd("ERRO DE BANCO DE DADOS: " . $e->getMessage());
        }
    }


    // --- IMPRESSÕES ---
    public function imprimirOS($id)
    {
        $os = ReceitaOtica::with(['cliente'])->findOrFail($id);
        $config = DB::table('empresas')->where('id', session('user_logged')['empresa'] ?? 1)->first();

        // Retorna a view HTML direta (o navegador cuida da impressão)
        return view('otica.print_os', compact('os', 'config'));
    }

    public function imprimirRecibo($id)
    {
        $os = ReceitaOtica::with(['cliente'])->findOrFail($id);
        $config = DB::table('empresas')->where('id', session('user_logged')['empresa'] ?? 1)->first();

        // Retorna a view HTML direta
        return view('otica.print_recibo', compact('os', 'config'));
    }

    // --- MUDANÇA DE STATUS RÁPIDA NA LISTAGEM ---
    public function alterarStatus(Request $request)
    {
        try {
            $os = ReceitaOtica::findOrFail($request->id);

            // Definição da hierarquia de passos
            $pesos = [
                'orcamento'   => 1,
                'pendente'    => 2,
                'laboratorio' => 3,
                'conferencia' => 4,
                'pronto'      => 5,
                'entregue'    => 6
            ];

            if ($os->status == 'entregue' || $os->venda_id) {
                return response()->json(['success' => false, 'message' => 'Esta OS já foi faturada e não pode ser alterada.']);
            }

            $pesoAtual = $pesos[$os->status] ?? 0;
            $pesoNovo  = $pesos[$request->status] ?? 0;

            // REGRA: Só pode ir para frente no fluxo
            if ($pesoNovo < $pesoAtual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movimentação bloqueada! O status só pode avançar no fluxo operacional.'
                ]);
            }

            $os->status = $request->status;
            $os->save();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // --- FATURAMENTO (GERAÇÃO DE VENDA PARA O ERP) ---
    public function faturar(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $os = ReceitaOtica::findOrFail($id);

            if ($os->status == 'entregue' || $os->venda_id) {
                return redirect()->back()->with('error', 'Esta OS já possui uma venda vinculada.');
            }

            $user_session = session('user_logged');
            $empresa_id   = $user_session['empresa'] ?? 1;
            $usuario_id   = $user_session['id'] ?? 1;
            $filial_id    = !empty($user_session['filial']) ? $user_session['filial'] : null;

            // 1. BUSCA A NATUREZA PADRÃO NA TABELA config_notas
            $configNota = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
            $natureza_id = $configNota->nat_op_padrao ?? null;

            // Se não achou na config, tenta pegar a primeira disponível para não dar erro
            if (!$natureza_id) {
                $n = \App\Models\NaturezaOperacao::where('empresa_id', $empresa_id)->first();
                $natureza_id = $n->id ?? null;
            }

            if (!$natureza_id) {
                return redirect()->back()->with('error', 'Configuração de Natureza de Operação não encontrada.');
            }

            $tipo = $request->get('tipo', 'nfe');
            $valorTotal = $os->valor_lente + $os->valor_armacao;

            if ($tipo == 'pdv') {
                // 2. LANÇA COMO PRÉ-VENDA NÍVEL 2 (PARA APARECER NO PDV)
                $preVenda = \App\Models\VendaCaixaPreVenda::create([
                    'empresa_id'     => $empresa_id,
                    'filial_id'      => $filial_id,
                    'usuario_id'     => $usuario_id,
                    'cliente_id'     => $os->cliente_id,
                    'natureza_id'    => $natureza_id,
                    'valor_total'    => $valorTotal,
                    'estado'         => 'DISPONIVEL',
                    'prevenda_nivel' => 2, // Ajuste solicitado para aparecer no PDV
                    'observacao'     => "Origem OS Ótica #" . $os->id
                ]);

                // Itens da Pré-venda
                if ($os->lente_id && $os->valor_lente > 0) {
                    \App\Models\ItemVendaCaixaPreVenda::create([
                        'venda_caixa_prevenda_id' => $preVenda->id,
                        'produto_id' => $os->lente_id,
                        'quantidade' => $os->qtd_lente ?? 1,
                        'valor' => $os->valor_lente / ($os->qtd_lente ?? 1)
                    ]);
                }

                if ($os->armacao_id && $os->valor_armacao > 0) {
                    \App\Models\ItemVendaCaixaPreVenda::create([
                        'venda_caixa_prevenda_id' => $preVenda->id,
                        'produto_id' => $os->armacao_id,
                        'quantidade' => $os->qtd_armacao ?? 1,
                        'valor' => $os->valor_armacao / ($os->qtd_armacao ?? 1)
                    ]);
                }

                $os->update(['status' => 'entregue', 'venda_id' => $preVenda->id]);
                return redirect('/frenteCaixa')->with('success', 'Pré-venda gerada com sucesso!');

            } else {
                // 3. FLUXO NF-E (RETAGUARDA)
                $venda = \App\Models\Venda::create([
                    'empresa_id'  => $empresa_id,
                    'filial_id'   => $filial_id,
                    'usuario_id'  => $usuario_id,
                    'cliente_id'  => $os->cliente_id,
                    'natureza_id' => $natureza_id,
                    'valor_total' => $valorTotal,
                    'estado'      => 'DISPONIVEL',
                    'observacao'  => "Origem OS Ótica #" . $os->id
                ]);

                // Itens da Venda
                if ($os->lente_id && $os->valor_lente > 0) {
                    \App\Models\ItemVenda::create([
                        'empresa_id' => $empresa_id, 'venda_id' => $venda->id,
                        'produto_id' => $os->lente_id, 'quantidade' => $os->qtd_lente ?? 1,
                        'valor' => $os->valor_lente / ($os->qtd_lente ?? 1)
                    ]);
                }

                if ($os->armacao_id && $os->valor_armacao > 0) {
                    \App\Models\ItemVenda::create([
                        'empresa_id' => $empresa_id, 'venda_id' => $venda->id,
                        'produto_id' => $os->armacao_id, 'quantidade' => $os->qtd_armacao ?? 1,
                        'valor' => $os->valor_armacao / ($os->qtd_armacao ?? 1)
                    ]);
                }

                $os->update(['status' => 'entregue', 'venda_id' => $venda->id]);
                return redirect('/vendas/edit/'.$venda->id)->with('success', 'Venda gerada para NF-e!');
            }
        });
    }



    // --- BUSCA INTELIGENTE DE CLIENTES (SELECT2) ---
    public function buscarClientes(Request $request)
    {
        try {
            $search = $request->q;
            $empresa_id = session('user_logged')['empresa'] ?? 1;

            $data = Cliente::where('empresa_id', $empresa_id)
                ->where(function($q) use ($search) {
                    $q->where('razao_social', 'like', "%{$search}%")
                        ->orWhere('cpf_cnpj', 'like', "%{$search}%");
                })->limit(20)->get();

            return response()->json($data->map(function($item) {
                return ['id' => $item->id, 'text' => $item->razao_social . " (" . $item->cpf_cnpj . ")"];
            }));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // --- BUSCA INTELIGENTE DE PRODUTOS (SELECT2) ---
    public function buscarProdutos(Request $request)
    {
        try {
            $search = $request->q;
            $empresa_id = session('user_logged')['empresa'] ?? 1;

            $data = Produto::where('empresa_id', $empresa_id)
                ->where(function($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                        ->orWhere('referencia', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                })->limit(20)->get();

            return response()->json($data->map(function($item) {
                return [
                    'id' => $item->id,
                    'text' => ($item->referencia ? $item->referencia . " - " : "") . $item->nome,
                    'preco' => $item->valor_venda
                ];
            }));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // --- CADASTRO RÁPIDO DE CLIENTE (MODAL) ---
    public function clienteRapido(Request $request)
    {
        try {
            $empresa_id = session('user_logged')['empresa'] ?? 1;

            $cliente = Cliente::create([
                'empresa_id'       => $empresa_id,
                'razao_social'     => $request->razao_social,
                'nome_fantasia'    => $request->razao_social,
                'cpf_cnpj'         => $request->cpf_cnpj ?? '000.000.000-00',
                'telefone'         => $request->telefone,
                'data_nascimento'  => $request->data_nascimento,
                'rua'              => $request->rua ?? '',
                'bairro'           => $request->bairro ?? '',
                'numero'           => $request->numero ?? 'S/N',
                'cidade_id'        => 1, // ID padrão para evitar erro de banco
                'consumidor_final' => 1,
                'contribuinte'     => 0,
                'inativo'          => 0
            ]);

            return response()->json(['success' => true, 'id' => $cliente->id, 'nome' => $cliente->razao_social]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // --- EXCLUSÃO COM TRAVA DE SEGURANÇA ---
    public function delete($id) {
        return $this->removerOS($id);
    }

    // --- EXCLUSÃO COM TRAVA DE SEGURANÇA ---
    public function destroy($id) {
        return $this->removerOS($id);
    }

    // --- BLOCO DE EXCLUSÃO SEGURO ---
    private function removerOS($id)
    {
        try {
            $os = \App\Models\ReceitaOtica::find($id);
            if ($os) {
                if ($os->venda_id || $os->status == 'entregue') {
                    return redirect()->route('otica.index')->with('error', 'Ação Bloqueada: Não pode excluir uma OS que já foi para o Caixa.');
                }
                $os->delete();
                return redirect()->route('otica.index')->with('success', 'Ordem de Serviço excluída com sucesso!');
            }
            return redirect()->route('otica.index');
        } catch (\Exception $e) {
            return redirect()->route('otica.index')->with('error', 'Erro ao excluir: ' . $e->getMessage());
        }
    }

}
