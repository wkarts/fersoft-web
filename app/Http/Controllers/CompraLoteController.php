<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\ItemCompra;
use App\Models\Fornecedor;
use App\Models\ContaPagar;
use App\Models\ItemContaEmpresa;
use App\Models\ContaEmpresa;
use App\Models\Produto;
use App\Models\Estoque;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class CompraLoteController extends Controller
{
    public function index()
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];

        $produtos = Produto::where('empresa_id', $empresa_id)
            ->where('inativo', false)->orderBy('nome')->get();

        $contas = ContaEmpresa::where('empresa_id', $empresa_id)
            ->where('status', 1)->get();

        $categorias = \App\Models\CategoriaConta::where('empresa_id', $empresa_id)
            ->where('tipo', 'pagar')->orderBy('nome')->get();

        $naturezas = \App\Models\NaturezaOperacao::where('empresa_id', $empresa_id)->get();

        return view('compras_lote.index', compact('produtos', 'contas', 'categorias', 'naturezas'))
            ->with('title', 'Importação de Compra em Lote');
    }

    public function importar(Request $request)
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];
        $usuario_id = $sessionData['id'];
        $filial_id  = $sessionData['local_padrao'] ?? null;

        $meiosPagamento = [
            '01' => 'Dinheiro', '17' => 'Pix', '15' => 'Boleto Bancário', '03' => 'Cartão de Crédito', '99' => 'Outros'
        ];
        // Nome descritivo (ex: "Pix")
        $tipoPagamentoDescricao = $meiosPagamento[$request->tipo_pagamento_nfe] ?? 'Outros';

        $rows = Excel::toArray([], $request->file('file'))[0];
        $resumo = ['sucesso' => 0, 'rejeicao' => 0, 'erros' => []];
        $precoUnitario = __replace($request->preco_unitario);

        foreach ($rows as $index => $row) {
            if ($index < 10 || empty($row[3])) continue;

            try {
                $dadosProcessamento = DB::transaction(function () use ($row, $request, $empresa_id, $usuario_id, $filial_id, $precoUnitario, $index, $tipoPagamentoDescricao) {

                    $cpfCnpjLimpo = preg_replace('/[^0-9]/', '', $row[3]);
                    $valor = abs((float)str_replace(',', '.', $row[4]));
                    $quantidade = $valor / $precoUnitario;
                    $dataRetroativaForm = $request->data_retroativa ?? date('Y-m-d');

                    try {
                        if ($row[0] instanceof \DateTime) {
                            $dataVencimentoPlanilha = $row[0]->format('Y-m-d');
                        } else {
                            $dataLimpa = trim($row[0]);
                            $dataVencimentoPlanilha = Carbon::parse(str_replace('/', '-', $dataLimpa))->format('Y-m-d');
                        }
                    } catch (\Exception $eDate) {
                        $dataVencimentoPlanilha = $dataRetroativaForm;
                    }

                    $fornecedor = Fornecedor::where('empresa_id', $empresa_id)
                        ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '') = ?", [$cpfCnpjLimpo])
                        ->first();

                    if (!$fornecedor) throw new \Exception("Fornecedor $cpfCnpjLimpo não localizado.");

                    // 1. Compra
                    $compra = Compra::create([
                        'empresa_id' => $empresa_id,
                        'fornecedor_id' => $fornecedor->id,
                        'usuario_id' => $usuario_id,
                        'valor' => $valor,
                        'estado' => 'NOVO',
                        'observacao' => $request->observacao ?? 'Ref. Compra de Mercadorias',
                        'natureza_id' => $request->natureza_id,
                        'tipo_pagamento' => $request->tipo_pagamento_nfe, // Código numérico para NFe
                        'filial_id' => $filial_id,
                        'estoque_atualizado' => 1
                    ]);

                    $horaAgora = date('H:i:s');
                    $dataEmissaoCompleta = $dataRetroativaForm . ' ' . $horaAgora;

                    DB::table('compras')->where('id', $compra->id)->update([
                        'created_at' => $dataEmissaoCompleta,
                        'updated_at' => $dataEmissaoCompleta,
                        'data_emissao' => $dataEmissaoCompleta,
                        'data_saida' => $dataEmissaoCompleta,
                        'data_retroativa' => $dataRetroativaForm,
                        'peso_liquido' => $quantidade,
                        'peso_bruto' => $quantidade
                    ]);

                    ItemCompra::create([
                        'compra_id' => $compra->id,
                        'produto_id' => $request->produto_id,
                        'quantidade' => $quantidade,
                        'valor_unitario' => $precoUnitario,
                        'unidade_compra' => 'UN',
                    ]);

                    // 2. Estoque (Kardex)
                    $estoque = Estoque::where('produto_id', $request->produto_id)->where('empresa_id', $empresa_id)->first();
                    $saldoMomento = $quantidade;
                    if ($estoque) {
                        $estoque->quantidade += $quantidade;
                        $estoque->save();
                        $saldoMomento = $estoque->quantidade;
                    } else {
                        Estoque::create(['produto_id' => $request->produto_id, 'quantidade' => $quantidade, 'empresa_id' => $empresa_id, 'filial_id' => $filial_id]);
                    }

                    $idMovimentacao = DB::table('stock_movements')->insertGetId([
                        'empresa_id' => $empresa_id,
                        'filial_id' => $filial_id,
                        'usuario_id' => $usuario_id,
                        'produto_id' => $request->produto_id,
                        'contexto' => 'compra',
                        'tipo' => 'entrada',
                        'quantidade' => $quantidade,
                        'custo_unitario' => $precoUnitario,
                        'valor_total' => $valor,
                        'origem_tipo' => 'compras',
                        'origem_id' => $compra->id,
                        'idempotency_key' => 'compra_lote_' . $compra->id . '_' . uniqid(),
                        'movimentado_em' => $dataEmissaoCompleta,
                        'metadata' => json_encode(['observacao' => 'COMPRA DE MERCADORIA REF. NF-E']),
                        'saldo_momento' => $saldoMomento,
                        'created_at' => $dataEmissaoCompleta,
                        'updated_at' => $dataEmissaoCompleta
                    ]);

                    // 3. Contas a Pagar (Agora com descrição "Pix")
                    $cp = ContaPagar::create([
                        'empresa_id' => $empresa_id,
                        'fornecedor_id' => $fornecedor->id,
                        'compra_id' => $compra->id,
                        'valor_integral' => $valor,
                        'valor_pago' => $valor,
                        'data_emissao' => $dataRetroativaForm,
                        'data_vencimento' => $dataVencimentoPlanilha,
                        'data_pagamento' => $dataVencimentoPlanilha,
                        'usuario_baixa_id' => $usuario_id,
                        'status' => 1,
                        'referencia' => 'COMPRA DE MERCADORIA REF. NF-E',
                        'tipo_pagamento' => $tipoPagamentoDescricao, // Grava "Pix"
                        'categoria_id' => $request->categoria_id,
                        'usuario_id' => $usuario_id,
                        'created_at' => $dataEmissaoCompleta
                    ]);

                    $contaEmp = ContaEmpresa::findOrFail($request->conta_id);
                    $contaEmp->saldo -= $valor;
                    $contaEmp->save();

                    // 4. Extrato (Agora com descrição "Pix" e user_id)
                    $idItemConta = ItemContaEmpresa::create([
                        'empresa_id' => $empresa_id,
                        'filial_id' => $filial_id,
                        'conta_id' => $request->conta_id,
                        'valor' => $valor,
                        'tipo' => 'saida',
                        'data_pagamento' => $dataVencimentoPlanilha,
                        'descricao' => "Pgto {$fornecedor->razao_social} Ref. Compra",
                        'tipo_pagamento' => $tipoPagamentoDescricao, // Grava "Pix"
                        'conta_pagar_id' => $cp->id,
                        'origem' => 'ContaPagar',
                        'usuario_id' => $usuario_id,
                        'user_id' => $usuario_id, // Gravando coluna user_id
                        'categoria_id' => $request->categoria_id,
                        'created_at' => $dataEmissaoCompleta
                    ])->id;

                    return [
                        'compra_id' => $compra->id,
                        'cp_id' => $cp->id,
                        'item_conta_id' => $idItemConta,
                        'stock_mov_id' => $idMovimentacao,
                        'razao_social' => $fornecedor->razao_social
                    ];
                });

                // ETAPA 2: DISPARO FISCAL (Update pós-processamento com número da nota)
                try {
                    $response = Http::withHeaders([
                        'Cookie' => request()->header('cookie'),
                        'X-CSRF-TOKEN' => csrf_token()
                    ])->asForm()->post(url('/compras/gerarEntrada'), [
                        'compra_id' => $dadosProcessamento['compra_id'],
                        'natureza' => $request->natureza_id,
                        'tipo_pagamento' => $request->tipo_pagamento_nfe
                    ]);

                    $resultado = $response->json();
                    if ($resultado['sucesso'] ?? false) {
                        $compraFinal = Compra::find($dadosProcessamento['compra_id']);
                        $nfeNum = $compraFinal->numero_emissao;

                        $compraFinal->update(['estado' => 'APROVADO']);

                        ContaPagar::where('id', $dadosProcessamento['cp_id'])->update([
                            'referencia' => "COMPRA DE MERCADORIA REF. NF-E $nfeNum"
                        ]);

                        ItemContaEmpresa::where('id', $dadosProcessamento['item_conta_id'])->update([
                            'descricao' => "Pgto {$dadosProcessamento['razao_social']} Ref. Compra $nfeNum"
                        ]);

                        DB::table('stock_movements')->where('id', $dadosProcessamento['stock_mov_id'])->update([
                            'metadata' => json_encode([
                                'observacao' => "COMPRA DE MERCADORIA REF. NF-E $nfeNum",
                                'nfe' => $nfeNum
                            ])
                        ]);

                        $resumo['sucesso']++;
                    } else {
                        $resumo['rejeicao']++;
                        $resumo['erros'][] = "Compra #".$dadosProcessamento['compra_id'].": " . ($resultado['mensagem'] ?? 'Rejeitada SEFAZ');
                    }
                } catch (\Exception $eNfe) {
                    $resumo['rejeicao']++;
                    $resumo['erros'][] = "Compra #".$dadosProcessamento['compra_id']." salva. Falha fiscal.";
                }

            } catch (\Exception $e) {
                $resumo['rejeicao']++;
                $resumo['erros'][] = "Linha " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return view('compras_lote.resumo', compact('resumo'))->with('title', 'Resumo da Importação');
    }
}
