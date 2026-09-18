<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesagem;
use App\Models\Compra;
use App\Models\ItemCompra;
use App\Models\ContaPagar;
use App\Models\ContaEmpresa;
use App\Models\ItemContaEmpresa;
use App\Models\Estoque;
use App\Models\TabelaPrecoNfe;
use App\Models\TabelaPrecoItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Produto;
use App\Models\Fornecedor;
use App\Models\Cidade;

class PesagemNfeController extends Controller
{
    public function index(Request $request)
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];

        $data_inicio = $request->data_inicio ?? date('Y-m-01');
        $data_fim = $request->data_fim ?? date('Y-m-t');
        $status_pesagem = $request->status_pesagem ?? 'pendentes';
        $fornecedor_id = $request->fornecedor_id ?? 'todos';

        $pesagensQuery = \App\Models\Pesagem::leftJoin('fornecedors', 'pesagens.fornecedor_id', '=', 'fornecedors.id')
            ->leftJoin('compras', 'pesagens.compra_id', '=', 'compras.id')
            ->where('pesagens.empresa_id', $empresa_id)
            ->whereBetween(DB::raw('DATE(pesagens.dt_registro)'), [$data_inicio, $data_fim]);

        if ($fornecedor_id !== 'todos') {
            $pesagensQuery->where('pesagens.fornecedor_id', $fornecedor_id);
        }

        // --- FILTRO CORRIGIDO ---
        // Agora ele olha se a Compra (NF-e) já foi gerada ou não
        if ($status_pesagem === 'pendentes') {
            $pesagensQuery->where('pesagens.status', 'concluído')
                          ->whereNull('pesagens.compra_id'); // Não tem NF gerada
        } elseif ($status_pesagem === 'emitidas') {
            $pesagensQuery->where('pesagens.status', 'concluído')
                          ->whereNotNull('pesagens.compra_id'); // Já tem NF gerada
        } elseif ($status_pesagem === 'todos') {
            $pesagensQuery->where('pesagens.status', 'concluído');
        }

        $pesagens = $pesagensQuery->select(
            'pesagens.*', 
            'fornecedors.razao_social as fornecedor_nome',
            'compras.numero_emissao as nfe_numero',
            'compras.estado as nfe_estado',
            'compras.valor as nfe_valor'
        )
        ->orderBy('pesagens.id', 'desc')
        ->get();

        $fornecedores = \App\Models\Fornecedor::where('empresa_id', $empresa_id)->get();
        $contas = \App\Models\ContaEmpresa::where('empresa_id', $empresa_id)->where('status', 1)->get();
        $naturezas = \App\Models\NaturezaOperacao::where('empresa_id', $empresa_id)->get();
        $categorias = \App\Models\CategoriaConta::where('empresa_id', $empresa_id)->where('tipo', 'pagar')->orderBy('nome')->get();

        return view('pesagem_nfe.index', compact(
            'pesagens', 'fornecedores', 'contas', 'naturezas', 'categorias',
            'data_inicio', 'data_fim', 'status_pesagem', 'fornecedor_id'
        ))->with('title', 'Emissão de NF-e de Pesagens');
    }

    public function processar(Request $request)
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];
        $usuario_id = $sessionData['id'];
        
        $pesagens_ids = $request->pesagens;
        if(empty($pesagens_ids)) {
            return redirect()->back()->with('resumo', ['sucesso' => 0, 'erros' => ['Nenhuma pesagem selecionada.']]);
        }

        $natureza_id = $request->natureza_id;
        $tipo_pagamento_nfe = $request->tipo_pagamento_nfe; // NOVO CAMPO
        $conta_id = $request->conta_id;
        $data_emissao = $request->data_emissao;
        $data_pagamento = $request->data_pagamento;
        $integrar_financeiro = $request->has('integrar_financeiro');
        $pagamento_a_vista_financeiro = $request->has('pagamento_a_vista');
    	$categoria_id = $request->categoria_id;

        // IDENTIFICA SE A NOTA É À VISTA PARA A SEFAZ
        $data_em_iso = substr($data_emissao, 0, 10);
        $data_pg_iso = substr($data_pagamento, 0, 10);
        $is_a_vista = ($pagamento_a_vista_financeiro || $data_em_iso === $data_pg_iso || $tipo_pagamento_nfe == '01');

        $resumo = ['sucesso' => 0, 'erros' => []];

        foreach ($pesagens_ids as $pesagem_id) {
            try {
                $pesagem = Pesagem::findOrFail($pesagem_id);
                
                // TRAVA DE SEGURANÇA
                if (!empty($pesagem->compra_id)) {
                    throw new \Exception("Esta pesagem já foi processada anteriormente (Compra #{$pesagem->compra_id}). Corrija a rejeição diretamente na rotina de Compras.");
                }
                
                $fornecedor_id = $pesagem->fornecedor_id;
                if (!$fornecedor_id) throw new \Exception("Esta pesagem não tem um fornecedor vinculado.");

                $fornecedor = Fornecedor::find($fornecedor_id);
                if (!$fornecedor) throw new \Exception("Fornecedor não encontrado no sistema.");

                $errosCadastro = [];
                $doc = preg_replace('/[^0-9]/', '', $fornecedor->cpf_cnpj);
                if (empty($doc)) $errosCadastro[] = "CPF/CNPJ não preenchido";
                if (empty($fornecedor->rua)) $errosCadastro[] = "Endereço/Rua";
                if (empty($fornecedor->numero)) $errosCadastro[] = "Número do endereço";
                if (empty($fornecedor->bairro)) $errosCadastro[] = "Bairro";
                $cep = preg_replace('/[^0-9]/', '', $fornecedor->cep);
                if (empty($cep) || strlen($cep) != 8) $errosCadastro[] = "CEP inválido ou incompleto";
                if (empty($fornecedor->cidade_id)) $errosCadastro[] = "Cidade não vinculada";

                if (count($errosCadastro) > 0) {
                    throw new \Exception("Cadastro incompleto: " . implode(', ', $errosCadastro) . ".");
                }

                if (strlen($doc) == 11) {
                    if (isset($fornecedor->ie_tipo) && $fornecedor->ie_tipo != '0') $fornecedor->update(['ie_tipo' => '0']); 
                    elseif (isset($fornecedor->contrib) && $fornecedor->contrib != 0) $fornecedor->update(['contrib' => 0]);
                }

                $tickets = DB::table('tickets_pesagem')->where('pesagem_id', $pesagem->id)->get();
                if ($tickets->isEmpty()) throw new \Exception("A pesagem #{$pesagem_id} não possui nenhum ticket registrado.");

                $dadosProcessamento = DB::transaction(function () use ($pesagem, $tickets, $natureza_id, $conta_id, $data_emissao, $data_pagamento, $integrar_financeiro, $pagamento_a_vista_financeiro, $tipo_pagamento_nfe, $empresa_id, $usuario_id, $categoria_id, $fornecedor_id) {                    
                    
                    $valor_total_compra = 0;
                    $peso_total_nfe = 0;

                    $compra = Compra::create([
                        'empresa_id' => $empresa_id,
                        'fornecedor_id' => $fornecedor_id,
                        'usuario_id' => $usuario_id,
                        'valor' => 0, 
                        'estado' => 'NOVO',
                        'observacao' => 'Ref. Pesagem #' . $pesagem->id,
                        'natureza_id' => $natureza_id,
                        'tipo_pagamento' => $tipo_pagamento_nfe, 
                        'data_emissao' => $data_emissao . ' ' . date('H:i:s'),
                        'estoque_atualizado' => 1
                    ]);

                    $itens_agrupados = [];

                    foreach ($tickets as $ticket) {
                        $produto_original_id = $ticket->produto_id;
                        $peso_balanca = $ticket->peso; 
                        
                        $produto_original = Produto::find($produto_original_id);
                        $produto_final_id = ($produto_original && $produto_original->produto_referenciado_id) 
                                            ? $produto_original->produto_referenciado_id 
                                            : $produto_original_id;

                        $preco_fornecedor = TabelaPrecoItem::where('produto_id', $produto_original_id)->first()->valor_kg ?? 0;
                        $preco_nfe_tabela = TabelaPrecoNfe::where('produto_id', $produto_final_id)->where('empresa_id', $empresa_id)->first();
                        $preco_nfe = $preco_nfe_tabela ? $preco_nfe_tabela->preco_nfe : $preco_fornecedor;

                        $total_pagar_item = $peso_balanca * $preco_fornecedor; 
                        $peso_para_nfe = $preco_nfe > 0 ? ($total_pagar_item / $preco_nfe) : $peso_balanca;       

                        $valor_total_compra += $total_pagar_item;
                        $peso_total_nfe += $peso_para_nfe;

                        if (isset($itens_agrupados[$produto_final_id])) {
                            $itens_agrupados[$produto_final_id]['quantidade'] += $peso_para_nfe;
                        } else {
                            $itens_agrupados[$produto_final_id] = [
                                'produto_id' => $produto_final_id,
                                'quantidade' => $peso_para_nfe,
                                'valor_unitario' => $preco_nfe
                            ];
                        }
                    }

                    $compra->valor = $valor_total_compra;
                    $compra->peso_liquido = $peso_total_nfe;
                    $compra->peso_bruto = $peso_total_nfe;
                    $compra->save();

                    foreach ($itens_agrupados as $item) {
                        ItemCompra::create([
                            'compra_id' => $compra->id,
                            'produto_id' => $item['produto_id'],
                            'quantidade' => $item['quantidade'],
                            'valor_unitario' => $item['valor_unitario'],
                            'unidade_compra' => 'KG',
                        ]);

                        $estoque = Estoque::firstOrNew(['produto_id' => $item['produto_id'], 'empresa_id' => $empresa_id]);
                        $estoque->quantidade += $item['quantidade'];
                        $estoque->save();

                        DB::table('stock_movements')->insert([
                            'empresa_id' => $empresa_id,
                            'usuario_id' => $usuario_id,
                            'produto_id' => $item['produto_id'],
                            'contexto' => 'compra',
                            'tipo' => 'entrada',
                            'quantidade' => $item['quantidade'],
                            'custo_unitario' => $item['valor_unitario'],
                            'movimentado_em' => $data_emissao . ' ' . date('H:i:s'),
                            'idempotency_key' => 'compra_nfe_pesagem_' . $compra->id . '_' . uniqid(),
                        ]);
                    }

                    $cp_id = null;
                    if ($integrar_financeiro) {
                        $cp = ContaPagar::create([
                            'empresa_id' => $empresa_id,
                            'fornecedor_id' => $fornecedor_id,
                            'compra_id' => $compra->id,
                            'valor_integral' => $valor_total_compra,
                            'valor_pago' => $pagamento_a_vista_financeiro ? $valor_total_compra : 0,
                            'data_emissao' => $data_emissao,
                            'data_vencimento' => $data_pagamento,
                            'data_pagamento' => $pagamento_a_vista_financeiro ? $data_pagamento : null,
                            'status' => $pagamento_a_vista_financeiro ? 1 : 0, 
                            'referencia' => 'Ref. Ticket Pesagem Nº ' . $pesagem->id,
                            'categoria_id' => $categoria_id,
                        ]);
                        $cp_id = $cp->id;

                        if ($pagamento_a_vista_financeiro) {
                            $contaEmp = ContaEmpresa::findOrFail($conta_id);
                            $contaEmp->saldo -= $valor_total_compra;
                            $contaEmp->save();

                            ItemContaEmpresa::create([
                                'empresa_id' => $empresa_id,
                                'conta_id' => $conta_id,
                                'valor' => $valor_total_compra,
                                'tipo' => 'saida',
                                'data_pagamento' => $data_pagamento,
                                'descricao' => "Pgto Ref. Ticket Pesagem Nº {$pesagem->id}",
                                'conta_pagar_id' => $cp->id,
                              	'categoria_id' => $categoria_id,
                            ]);
                        }
                    }

                    $pesagem->update(['status' => 'concluído', 'compra_id' => $compra->id]);

                    return [
                        'compra_id' => $compra->id,
                        'cp_id' => $cp_id
                    ];
                });

                // --- SOLUÇÃO PARA O ERRO 853 DA SEFAZ ---
                // Se o pagamento for à vista, removemos o vínculo da fatura ANTES de gerar o XML
                if ($is_a_vista && !empty($dadosProcessamento['cp_id'])) {
                    DB::table('conta_pagars')->where('id', $dadosProcessamento['cp_id'])->update(['compra_id' => null]);
                }

                // Envia para a Sefaz
                $response = Http::withHeaders([
                    'Cookie' => request()->header('cookie'),
                    'X-CSRF-TOKEN' => csrf_token()
                ])->asForm()->post(url('/compras/gerarEntrada'), [
                    'compra_id' => $dadosProcessamento['compra_id'],
                    'natureza' => $natureza_id,
                    'tipo_pagamento' => $tipo_pagamento_nfe
                ]);

                // --- DEVOLVE O VÍNCULO DA FATURA ---
                // Após o XML ser montado sem as tags de cobrança, devolvemos a conta para o ERP
                if ($is_a_vista && !empty($dadosProcessamento['cp_id'])) {
                    DB::table('conta_pagars')->where('id', $dadosProcessamento['cp_id'])->update(['compra_id' => $dadosProcessamento['compra_id']]);
                }

                if ($response->json('sucesso')) {
                    $resumo['sucesso']++;
                } else {
                    $resumo['erros'][] = "Pesagem #{$pesagem_id} salva, mas erro na Sefaz: " . $response->json('mensagem');
                }

            } catch (\Exception $e) {
                $resumo['erros'][] = "Erro na pesagem #{$pesagem_id}: " . $e->getMessage();
            }
        }

        return redirect()->back()->with('resumo', $resumo);
    }
}