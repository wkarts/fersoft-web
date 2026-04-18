<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\ItemCompra;
use App\Models\Fornecedor;
use App\Models\ContaPagar;
use App\Models\ItemContaEmpresa;
use App\Models\ContaEmpresa;
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

        $produtos = \App\Models\Produto::where('empresa_id', $empresa_id)
            ->where('inativo', false)->orderBy('nome')->get();

        $contas = \App\Models\ContaEmpresa::where('empresa_id', $empresa_id)
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
        $tipoPagamentoNome = $meiosPagamento[$request->tipo_pagamento_nfe] ?? 'Outros';

        $rows = Excel::toArray([], $request->file('file'))[0];
        $resumo = ['sucesso' => 0, 'rejeicao' => 0, 'erros' => []];
        $precoUnitario = __replace($request->preco_unitario); 

        foreach ($rows as $index => $row) {
            if ($index < 10 || empty($row[3])) continue; 

            try {
                $compraId = DB::transaction(function () use ($row, $request, $empresa_id, $usuario_id, $filial_id, $precoUnitario, $index, $tipoPagamentoNome) {
                    
                    $cpfCnpj = preg_replace('/[^0-9]/', '', $row[3]); 
                    $valor = abs((float)str_replace(',', '.', $row[4])); 

                    try {
                        if ($row[0] instanceof \DateTime) {
                            $dataPagamento = $row[0]->format('Y-m-d');
                        } else {
                            $dataLimpa = trim($row[0]);
                            if (empty($dataLimpa)) throw new \Exception("Data vazia");
                            $dataPagamento = Carbon::parse(str_replace('/', '-', $dataLimpa))->format('Y-m-d');
                        }
                    } catch (\Exception $eDate) {
                        throw new \Exception("Erro na data da linha " . ($index + 1));
                    }

                    $fornecedor = Fornecedor::where('cpf_cnpj', $cpfCnpj)->where('empresa_id', $empresa_id)->first();
                    if (!$fornecedor) throw new \Exception("Fornecedor $cpfCnpj não cadastrado.");

                    // 1. Criar Compra 
                    $compra = Compra::create([
                        'empresa_id' => $empresa_id,
                        'fornecedor_id' => $fornecedor->id,
                        'usuario_id' => $usuario_id,
                        'valor' => $valor,
                        'estado' => 'NOVO', 
                        'observacao' => $request->observacao ?? 'Ref. Compra de Mercadorias',
                        'natureza_id' => $request->natureza_id,
                        'tipo_pagamento' => $request->tipo_pagamento_nfe,
                        'filial_id' => $filial_id
                    ]);

                    // --- AJUSTE DE DATA RETROATIVA CORRIGIDO ---
                    $horaAgora = date('H:i:s');
                    $dataEmissaoForm = $request->data_retroativa ?? date('Y-m-d');
                    $dataSaidaForm = $request->data_saida ?? date('Y-m-d');

                    // Criamos a data completa com a hora de AGORA
                    $dataEmissaoCompleta = $dataEmissaoForm . ' ' . $horaAgora;
                    $dataSaidaCompleta   = $dataSaidaForm . ' ' . $horaAgora;

                    // Se a saída for em dia diferente, garantimos que seja no fim do dia
                    if ($dataSaidaForm > $dataEmissaoForm) {
                        $dataSaidaCompleta = $dataSaidaForm . ' 23:59:59';
                    }

                    // Atualizamos as colunas incluindo a data_retroativa da imagem
                    DB::table('compras')->where('id', $compra->id)->update([
                        'created_at' => $dataEmissaoCompleta,
                        'updated_at' => $dataEmissaoCompleta,
                        'data_emissao' => $dataEmissaoCompleta,
                        'data_saida' => $dataSaidaCompleta,
                        'data_retroativa' => $dataEmissaoForm // Nome exato da coluna na imagem
                    ]);
                    // -------------------------------------------

                    ItemCompra::create([
                        'compra_id' => $compra->id,
                        'produto_id' => $request->produto_id,
                        'quantidade' => $valor / $precoUnitario,
                        'valor_unitario' => $precoUnitario,
                        'unidade_compra' => 'UN',
                    ]);

                    $cp = ContaPagar::create([
                        'empresa_id' => $empresa_id,
                        'fornecedor_id' => $fornecedor->id,
                        'compra_id' => $compra->id,
                        'valor_integral' => $valor,
                        'valor_pago' => $valor,
                        'data_vencimento' => $dataPagamento,
                        'data_pagamento' => $dataPagamento,
                        'status' => 1, 
                        'referencia' => 'Compra de mercadorias',
                        'tipo_pagamento' => $tipoPagamentoNome,
                        'categoria_id' => $request->categoria_id,
                        'usuario_id' => $usuario_id
                    ]);

                    $contaEmp = ContaEmpresa::findOrFail($request->conta_id);
                    $contaEmp->saldo -= $valor;
                    $contaEmp->save();

                    ItemContaEmpresa::create([
                        'empresa_id' => $empresa_id,
                        'filial_id' => $filial_id,
                        'conta_id' => $request->conta_id,
                        'valor' => $valor,
                        'tipo' => 'saida',
                        'data_pagamento' => $dataPagamento,
                        'descricao' => "Pgto {$fornecedor->razao_social} Ref. Compra de Mercadorias",
                        'tipo_pagamento' => $tipoPagamentoNome,
                        'conta_pagar_id' => $cp->id,
                        'origem' => 'ContaPagar',
                        'usuario_id' => $usuario_id,
                        'categoria_id' => $request->categoria_id
                    ]);

                    return $compra->id;
                });

                // ETAPA 2: DISPARO FISCAL
                try {
                    $response = Http::withHeaders([
                        'Cookie' => request()->header('cookie'),
                        'X-CSRF-TOKEN' => csrf_token()
                    ])
                    ->asForm()
                    ->post(url('/compras/gerarEntrada'), [
                        'compra_id' => $compraId,
                        'natureza' => $request->natureza_id,
                        'tipo_pagamento' => $request->tipo_pagamento_nfe
                    ]);

                    $resultado = $response->json();
                    $sucessoSefaz = $resultado['sucesso'] ?? false;

                    if ($sucessoSefaz) {
                        $compraConfirmada = Compra::find($compraId);
                        $compraConfirmada->update(['estado' => 'APROVADO']);
                        $resumo['sucesso']++;
                    } else {
                        $resumo['rejeicao']++; 
                        $msgSefaz = $resultado['mensagem'] ?? $resultado['msg'] ?? 'Rejeitada pela SEFAZ.';
                        $resumo['erros'][] = "Compra #$compraId SEFAZ RECUSOU: " . $msgSefaz;
                    }
                } catch (\Exception $eNfe) {
                    $resumo['rejeicao']++;
                    $resumo['erros'][] = "Compra #$compraId salva. Falha de conexão com SEFAZ.";
                }

            } catch (\Exception $e) {
                $resumo['rejeicao']++;
                $resumo['erros'][] = "Linha " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        return view('compras_lote.resumo', compact('resumo'))->with('title', 'Resumo da Importação');
    }
}