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
        $empresa_id = $sessionData['empresa'] ?? null;
        $usuario_id = $sessionData['id'] ?? null;

        if (!$empresa_id) { return redirect('/login'); }

        $request->validate([
            'file' => 'required',
            'produto_id' => 'required',
            'preco_unitario' => 'required',
            'conta_id' => 'required',
            'natureza_id' => 'required'
        ]);

        $rows = Excel::toArray([], $request->file('file'))[0];
        $resumo = ['sucesso' => 0, 'rejeicao' => 0, 'erros' => []];
        $precoUnitario = __replace($request->preco_unitario); 

        foreach ($rows as $index => $row) {
            if ($index < 10 || empty($row[3])) continue; 

            try {
                // ETAPA 1: GRAVAR COMPRA E FINANCEIRO
                $compraId = DB::transaction(function () use ($row, $request, $empresa_id, $usuario_id, $precoUnitario, $index) {
                    
                    $cpfCnpj = preg_replace('/[^0-9]/', '', $row[3]); 
                    $valor = abs((float)str_replace(',', '.', $row[4])); 
                    $dataPagamento = Carbon::createFromFormat('d/m/Y', $row[0])->format('Y-m-d');

                    $fornecedor = Fornecedor::where('cpf_cnpj', $cpfCnpj)
                        ->where('empresa_id', $empresa_id)->first();

                    if (!$fornecedor) {
                        throw new \Exception("Linha " . ($index + 1) . ": Fornecedor $cpfCnpj não cadastrado.");
                    }

                    $compra = Compra::create([
                        'empresa_id' => $empresa_id,
                        'fornecedor_id' => $fornecedor->id,
                        'usuario_id' => $usuario_id,
                        'valor' => $valor,
                        'data_emissao' => $request->data_retroativa ?? date('Y-m-d'),
                        'data_saida' => $request->data_saida,
                        'estado' => 'NOVO', 
                        'observacao' => $request->observacao ?? 'Importação em lote',
                        'natureza_id' => $request->natureza_id,
                        'tipo_pagamento' => $request->tipo_pagamento_nfe,
                        'filial_id' => session('user_logged.local_padrao')
                    ]);

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
                        'tipo_pagamento' => $request->tipo_pagamento_nfe,
                        'categoria_id' => $request->categoria_id,
                        'usuario_id' => $usuario_id,
                        'numero_nota_fiscal' => 0
                    ]);

                    $contaEmp = ContaEmpresa::findOrFail($request->conta_id);
                    $contaEmp->saldo -= $valor;
                    $contaEmp->save();

                    ItemContaEmpresa::create([
                        'conta_id' => $request->conta_id,
                        'descricao' => "Pgto: {$fornecedor->razao_social} | Lote",
                        'tipo_pagamento' => $request->tipo_pagamento_nfe,
                        'valor' => $valor,
                        'tipo' => 'saida',
                        'data_pagamento' => $dataPagamento,
                        'conta_pagar_id' => $cp->id,
                        'origem' => 'ContaPagar',
                        'user_id' => $usuario_id,
                        'empresa_id' => $empresa_id
                    ]);

                    return $compra->id;
                });

                // ETAPA 2: EMISSÃO DA NOTA USANDO O MÉTODO LOCALIZADO
                try {
                    $compraManualController = app(\App\Http\Controllers\CompraManualController::class);
                    $requestNfe = new \Illuminate\Http\Request([
                        'id' => $compraId,
                        'natureza' => $request->natureza_id,
                        'tipo_pagamento' => $request->tipo_pagamento_nfe
                    ]);

                    $compraManualController->salvarNfFiscal($requestNfe); 

                    $compraConfirmada = Compra::find($compraId);
                    if($compraConfirmada->numero_emissao > 0) {
                        $compraConfirmada->update(['estado' => 'APROVADO']);
                        $resumo['sucesso']++;
                    } else {
                        $resumo['erros'][] = "Compra #$compraId salva, mas nota não autorizada.";
                    }
                } catch (\Exception $eNfe) {
                    $resumo['erros'][] = "Compra #$compraId salva, erro no fiscal: " . $eNfe->getMessage();
                }

            } catch (\Exception $e) {
                $resumo['rejeicao']++;
                $resumo['erros'][] = $e->getMessage();
            }
        }
        return view('compras_lote.resumo', compact('resumo'));
    }
}