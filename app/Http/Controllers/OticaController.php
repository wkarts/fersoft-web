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
use Illuminate\Support\Facades\Storage;

class OticaController extends BaseController
{
    public function __construct()
    {
        $this->model        = new ReceitaOtica();
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

    // --- SALVAMENTO E ATUALIZAÇÃO ---
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

            // Proteção contra alteração pós-venda faturada
            if ($os && ($os->status == 'entregue' || $os->venda_id)) {
                return redirect()->route('otica.index')->with('error', 'Esta OS está faturada e não pode receber alterações.');
            }

            $dados = $request->except(['_token', '_method', 'total_os', 'anexo_receita']);
            $dados['empresa_id'] = session('user_logged')['empresa'] ?? 1;

            // Upload do PDF/Foto da Receita no disco público do Laravel
            if ($request->hasFile('anexo_receita') && $request->file('anexo_receita')->isValid()) {
                if ($os && $os->anexo_receita) {
                    Storage::disk('public')->delete($os->anexo_receita);
                }
                $path = $request->file('anexo_receita')->store('receitas_otica', 'public');
                $dados['anexo_receita'] = $path;
            }

            // Corrige o formato do dinheiro
            if (!empty($dados['valor_armacao'])) $dados['valor_armacao'] = str_replace(['.', ','], ['', '.'], $dados['valor_armacao']);
            if (!empty($dados['valor_lente'])) $dados['valor_lente'] = str_replace(['.', ','], ['', '.'], $dados['valor_lente']);

            // Nomes dos produtos
            if (!empty($dados['armacao_id'])) {
                $prod = \App\Models\Produto::find($dados['armacao_id']);
                if ($prod) $dados['armacao'] = $prod->nome;
            }
            if (!empty($dados['lente_id'])) {
                $prod = \App\Models\Produto::find($dados['lente_id']);
                if ($prod) $dados['lente'] = $prod->nome;
            }

            // Calcula data de entrega
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
        return view('otica.print_os', compact('os', 'config'));
    }

    public function imprimirRecibo($id)
    {
        $os = ReceitaOtica::with(['cliente'])->findOrFail($id);
        $config = DB::table('empresas')->where('id', session('user_logged')['empresa'] ?? 1)->first();
        return view('otica.print_recibo', compact('os', 'config'));
    }

    // --- MUDANÇA DE STATUS RÁPIDA COM DISPARO DE WHATSAPP ---
    public function alterarStatus(Request $request)
    {
        try {
            $item = \App\Models\ClienteOtica::find($request->id);

            if (!$item) {
                return response()->json(['success' => false, 'message' => 'Ordem de Serviço não encontrada.']);
            }

            // --- INÍCIO DA TRAVA LINEAR DE STATUS ---
            $hierarquia = [
                'orcamento'   => 1,
                'pendente'    => 2,
                'laboratorio' => 3,
                'conferencia' => 4,
                'pronto'      => 5,
                'entregue'    => 6
            ];

            $nivelAtual = $hierarquia[$item->status] ?? 0;
            $nivelNovo  = $hierarquia[$request->status] ?? 0;

            if ($nivelNovo < $nivelAtual) {
                return response()->json([
                    'success' => false,
                    'message' => 'O sistema não permite retroceder o status da Ordem de Serviço para garantir a integridade.'
                ]);
            }
            // --- FIM DA TRAVA LINEAR DE STATUS ---

            $item->status = $request->status;
            $item->save();

            // SE O STATUS FOR PRONTO, DISPARA O WHATSAPP AUTOMÁTICO
            if ($request->status === 'pronto') {
                if ($item->cliente) {
                    $numeroOriginal = !empty($item->cliente->whatsapp) ? $item->cliente->whatsapp : ($item->cliente->telefone ?? '');
                    $numero = preg_replace('/[^0-9]/', '', $numeroOriginal);

                    if (strlen($numero) >= 10) {
                        if (substr($numero, 0, 2) !== '55') {
                            $numero = "55" . $numero;
                        }

                        $nomeCliente = $item->cliente->razao_social ?? 'Cliente';
                        $mensagem = "Olá, {$nomeCliente}! Sua Ordem de Serviço #{$item->id} está PRONTA e já pode ser retirada em nossa ótica.";

                        try {
                            $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                            $instanciaWhats->sendMessage($numero, $mensagem, $item->empresa_id);
                        } catch (\Throwable $e) {
                            \Log::error("Erro ao enviar Whats automático (OS {$item->id}): " . $e->getMessage());
                        }
                    }
                }
            }

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            \Log::error("Erro fatal ao alterar o status da OS {$request->id}: " . $e->getMessage() . " na linha " . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno detectado: ' . $e->getMessage() . ' (Linha: ' . $e->getLine() . ')'
            ]);
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

            $configNota = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
            $natureza_id = $configNota->nat_op_padrao ?? null;

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
                $preVenda = \App\Models\VendaCaixaPreVenda::create([
                    'empresa_id'     => $empresa_id,
                    'filial_id'      => $filial_id,
                    'usuario_id'     => $usuario_id,
                    'cliente_id'     => $os->cliente_id,
                    'natureza_id'    => $natureza_id,
                    'valor_total'    => $valorTotal,
                    'estado'         => 'DISPONIVEL',
                    'prevenda_nivel' => 2,
                    'observacao'     => "Origem OS Ótica #" . $os->id
                ]);

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

    public function buscarCidades(Request $request)
    {
        try {
            $search = $request->q;
            $uf = $request->uf;

            $query = \App\Models\Cidade::query();

            if (!empty($uf)) {
                $query->where('uf', $uf);
            }

            $query->where(function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            });

            $data = $query->orderByRaw("CASE WHEN nome = ? THEN 0 ELSE 1 END", [$search])
                ->limit(20)
                ->get();

            return response()->json($data->map(function($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->nome . " (" . $item->uf . ")"
                ];
            }));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cadastroRapidoCliente(Request $request)
    {
        try {
            $empresa_id = session('user_logged')['empresa'] ?? 1;

            if (empty($request->cidade_id)) {
                return response()->json(['success' => false, 'message' => 'A cidade é obrigatória para emissão de Notas Fiscais.']);
            }

            $whatsLimpo = preg_replace('/[^0-9]/', '', $request->whatsapp);
            if (empty($whatsLimpo) || intval($whatsLimpo) === 0) {
                $whatsappFinal = null;
            } else {
                if (strlen($whatsLimpo) == 11) {
                    $whatsappFinal = "(" . substr($whatsLimpo, 0, 2) . ") " . substr($whatsLimpo, 2, 5) . "-" . substr($whatsLimpo, 7);
                } elseif (strlen($whatsLimpo) == 10) {
                    $whatsappFinal = "(" . substr($whatsLimpo, 0, 2) . ") " . substr($whatsLimpo, 2, 4) . "-" . substr($whatsLimpo, 6);
                } else {
                    $whatsappFinal = $request->whatsapp;
                }
            }

            $telLimpo = preg_replace('/[^0-9]/', '', $request->telefone);
            if (empty($telLimpo) || intval($telLimpo) === 0) {
                $telefoneFinal = null;
            } else {
                if (strlen($telLimpo) == 11) {
                    $telefoneFinal = "(" . substr($telLimpo, 0, 2) . ") " . substr($telLimpo, 2, 5) . "-" . substr($telLimpo, 7);
                } elseif (strlen($telLimpo) == 10) {
                    $telefoneFinal = "(" . substr($telLimpo, 0, 2) . ") " . substr($telLimpo, 2, 4) . "-" . substr($telLimpo, 6);
                } else {
                    $telefoneFinal = $request->telefone;
                }
            }

            $cliente = Cliente::create([
                'empresa_id'       => $empresa_id,
                'razao_social'     => $request->razao_social,
                'nome_fantasia'    => $request->razao_social,
                'cpf_cnpj'         => $request->cpf_cnpj ?? '000.000.000-00',
                'telefone'         => $telefoneFinal,
                'whatsapp'         => $whatsappFinal,
                'cep'              => $request->cep,
                'data_nascimento'  => !empty($request->data_nascimento) ? $request->data_nascimento : null,
                'rua'              => $request->rua ?? '',
                'bairro'           => $request->bairro ?? '',
                'numero'           => $request->numero ?? 'S/N',
                'cidade_id'        => $request->cidade_id,
                'consumidor_final' => 1,
                'contribuinte'     => 0,
                'inativo'          => 0
            ]);

            return response()->json(['success' => true, 'id' => $cliente->id, 'nome' => $cliente->razao_social]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function enviarWhatsAppDireto(Request $request)
    {
        try {
            set_time_limit(10);

            $empresaId = session('user_logged')['empresa'] ?? 1;
            $usuarioId = session('user_logged')['id'] ?? 1;

            $this->empresa_id = $empresaId;
            $this->usuario_id = $usuarioId;

            if (empty($this->logService)) {
                $this->logService = new \App\Services\LogService($this->empresa_id, $this->usuario_id, $this->filial_id);
            }

            $numero = preg_replace('/[^0-9]/', '', $request->whatsapp);
            if (strlen($numero) < 10) {
                return response()->json(['success' => false, 'message' => 'O número de WhatsApp do cliente é inválido.']);
            }

            if (substr($numero, 0, 2) !== '55') {
                $numero = "55" . $numero;
            }

            if (!class_exists('\App\Utils\WhatsAppUtil')) {
                return response()->json(['success' => false, 'message' => 'A classe \App\Utils\WhatsAppUtil não foi encontrada no projeto.']);
            }

            $instanciaWhats = app('\App\Utils\WhatsAppUtil');

            if (method_exists($instanciaWhats, 'send')) {
                $instanciaWhats->send($numero, $request->mensagem);
            } elseif (method_exists($instanciaWhats, 'sendMessage')) {
                $instanciaWhats->sendMessage($numero, $request->mensagem, $empresaId);
            } else {
                return response()->json(['success' => false, 'message' => 'Nenhum método de envio válido (send ou sendMessage) foi encontrado na classe WhatsAppUtil.']);
            }

            $this->logService->registrar('whatsapp_send', 'WhatsApp_Otica', [
                'registro_id' => null,
                'dados_antes' => null,
                'dados_depois' => json_encode([
                    'empresa_id' => $empresaId,
                    'usuario_id' => $usuarioId,
                    'numero'     => $numero,
                    'mensagem'   => $request->mensagem,
                    'status'     => 'sucesso'
                ], JSON_UNESCAPED_UNICODE)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mensagem processada pelo servidor com sucesso!'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha fatal na execução do PHP: ' . $e->getMessage() . ' na linha ' . $e->getLine()
            ]);
        }
    }
}
