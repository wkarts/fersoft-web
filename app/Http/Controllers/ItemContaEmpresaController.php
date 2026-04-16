<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemContaEmpresa;
use App\Models\ConfigNota;
use App\Models\ContaEmpresa;
use Illuminate\Support\Facades\DB;

class ItemContaEmpresaController extends Controller
{
   public function store(Request $request) 
{
    try {
        $request->validate([
            'conta_id' => 'required',
            'data_pagamento' => 'required',
            'descricao' => 'required',
            'valor' => 'required',
            'tipo' => 'required',
            'plano_conta_id' => 'required' 
        ]);

        $valor = str_replace(['.', ','], ['', '.'], $request->valor);

        DB::transaction(function () use ($request, $valor) {
            // --- 1. LANÇAMENTO NA CONTA DE ORIGEM ---
            $contaOrigem = ContaEmpresa::findOrFail($request->conta_id);

            if ($request->tipo == 'saida') {
                $contaOrigem->saldo -= $valor;
            } else {
                $contaOrigem->saldo += $valor;
            }
            $contaOrigem->save();

            ItemContaEmpresa::create([
                'conta_id' => $request->conta_id,
                'data_pagamento' => $request->data_pagamento,
                'descricao' => $request->descricao,
                'valor' => $valor,
                'tipo' => $request->tipo,
                'categoria_id' => $request->plano_conta_id, 
                'tipo_pagamento' => 'dinheiro',
                'saldo_atual' => $contaOrigem->saldo,
                'user_id' => auth()->user() ? auth()->user()->id : (session('user_logged')['id'] ?? null),
                'empresa_id' => auth()->user() ? auth()->user()->empresa_id : (session('user_logged')['empresa_id'] ?? null)
            ]);

            // --- 2. LANÇAMENTO AUTOMÁTICO NA CONTA DE DESTINO (TRANSFERÊNCIA) ---
            // Verifica se foi selecionada uma conta de destino no formulário
            if ($request->filled('conta_destino_id')) {
                $contaDestino = ContaEmpresa::findOrFail($request->conta_destino_id);
                
                // Se na origem foi SAÍDA, no destino será ENTRADA (e vice-versa)
                $tipoDestino = ($request->tipo == 'saida') ? 'entrada' : 'saida';

                if ($tipoDestino == 'entrada') {
                    $contaDestino->saldo += $valor;
                } else {
                    $contaDestino->saldo -= $valor;
                }
                $contaDestino->save();

                ItemContaEmpresa::create([
                    'conta_id' => $request->conta_destino_id,
                    'data_pagamento' => $request->data_pagamento,
                    'descricao' => $request->descricao . " (Transf. de: " . $contaOrigem->nome . ")",
                    'valor' => $valor,
                    'tipo' => $tipoDestino,
                    'categoria_id' => $request->plano_conta_id, 
                    'tipo_pagamento' => 'dinheiro',
                    'saldo_atual' => $contaDestino->saldo,
                    'user_id' => auth()->user() ? auth()->user()->id : (session('user_logged')['id'] ?? null),
                    'empresa_id' => auth()->user() ? auth()->user()->empresa_id : (session('user_logged')['empresa_id'] ?? null),
                    'origem' => 'transferencia' // Sugestão: marcar para evitar exclusão manual de um lado só
                ]);
            }
        });

        session()->flash("mensagem_sucesso", "Lançamento realizado com sucesso!");
    } catch (\Exception $e) {
        session()->flash("mensagem_erro", "Erro: " . $e->getMessage());
    }
    return redirect()->back();
}

    public function deleteLancamento(Request $request)
    {
        $empresa_id = auth()->user() ? auth()->user()->empresa_id : (session('user_logged')['empresa_id'] ?? null);
        $config = ConfigNota::where('empresa_id', $empresa_id)->first();

        if (!$config || md5($request->senha) != $config->senha_remover) {
            session()->flash('mensagem_erro', 'Senha de autorização incorreta!');
            return redirect()->back();
        }

        try {
            DB::transaction(function () use ($request) {
                $item = ItemContaEmpresa::findOrFail($request->id);

                if ($item->conta_receber_id != null || $item->conta_pagar_id != null) {
                    throw new \Exception("Lançamento automático! Estorne pelo financeiro.");
                }

                $conta = ContaEmpresa::find($item->conta_id);
                if ($item->tipo == 'entrada') {
                    $conta->saldo -= $item->valor;
                } else {
                    $conta->saldo += $item->valor;
                }
                $conta->save();

                $item->delete();
            });

            session()->flash('mensagem_sucesso', 'Lançamento excluído!');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
        }

        return redirect()->back();
    }
}