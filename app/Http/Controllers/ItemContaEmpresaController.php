<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemContaEmpresa;
use App\Models\ConfigNota;
use App\Models\ContaEmpresa;
use Illuminate\Support\Facades\DB;

class ItemContaEmpresaController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Define para onde o BaseController deve redirecionar em caso de erro/sucesso
        $this->redirectPage = '/item-conta';
    }

    public function store(Request $request)
    {
        try {
            // Validação usando o método do BaseController que chama rules() e messages()
            if (!$this->validateRequest($request)) {
                return redirect()->back()->withInput();
            }

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
                    'user_id' => $this->usuario_id, // Herdado do BaseController
                    'empresa_id' => $this->empresa_id, // Herdado do BaseController
                    'origem' => 'manual'
                ]);

                // --- 2. LANÇAMENTO AUTOMÁTICO NA CONTA DE DESTINO (TRANSFERÊNCIA) ---
                if ($request->filled('conta_destino_id')) {
                    $contaDestino = ContaEmpresa::findOrFail($request->conta_destino_id);
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
                        'user_id' => $this->usuario_id,
                        'empresa_id' => $this->empresa_id,
                        'origem' => 'transferencia'
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
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        if (!$config || md5($request->senha) != $config->senha_remover) {
            session()->flash('mensagem_erro', 'Senha de autorização incorreta!');
            return redirect()->back();
        }

        try {
            DB::transaction(function () use ($request) {
                $item = ItemContaEmpresa::where('empresa_id', $this->empresa_id)
                    ->findOrFail($request->id);

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

    /**
     * Implementação obrigatória dos métodos abstratos do BaseController
     */
    protected function rules(): array
    {
        return [
            'conta_id' => 'required',
            'data_pagamento' => 'required',
            'descricao' => 'required',
            'valor' => 'required',
            'tipo' => 'required',
            'plano_conta_id' => 'required'
        ];
    }

    protected function messages(): array
    {
        return [
            'conta_id.required' => 'O campo conta é obrigatório.',
            'plano_conta_id.required' => 'O campo categoria é obrigatório.',
            'valor.required' => 'Informe o valor do lançamento.'
        ];
    }
}
