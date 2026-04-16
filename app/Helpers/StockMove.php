<?php

namespace App\Helpers;

use App\Models\Estoque;
use App\Models\Produto;
use App\Models\Empresa;
use App\Models\ConfigNota;
use Illuminate\Support\Facades\DB;

class StockMove {
	
	private function existStock($productId, $filial_id){
		return Estoque::where('produto_id', $productId)
			->when($filial_id > 0, function ($query) use ($filial_id) {
				return $query->where('filial_id', $filial_id);
			})
			->first();
	}

	// NOVO MÉTODO PARA REGISTAR A MOVIMENTAÇÃO NO HISTÓRICO
	private function registarMovimentacao($produtoId, $quantidade, $tipo, $origemTipo = null, $origemId = null, $filialId = null, $dataMovimento = null) 
    {
        $estoque = $this->existStock($produtoId, $filialId);
        $saldoAtual = $estoque ? $estoque->quantidade : 0;
        
        $usuarioId = session('user_logged')['id'] ?? (auth()->check() ? auth()->id() : null);
        $chaveUnica = uniqid(date('YmdHis')) . '-' . rand(10000, 99999);

        // LÓGICA DE DATA E HORA:
        if ($dataMovimento) {
            $timestamp = strtotime($dataMovimento);
            // Se a data veio zerada (00:00:00), mantemos o dia mas injetamos a hora atual
            if (date('H:i:s', $timestamp) == '00:00:00') {
                $dataRegistro = date('Y-m-d', $timestamp) . ' ' . date('H:i:s');
            } else {
                // Se a data já tem uma hora específica (vinda do XML completo), mantemos ela
                $dataRegistro = date('Y-m-d H:i:s', $timestamp);
            }
        } else {
            $dataRegistro = date('Y-m-d H:i:s');
        }

        \Illuminate\Support\Facades\DB::table('stock_movements')->insert([
            'empresa_id'      => session('user_logged')['empresa_id'] ?? 1,
            'filial_id'       => $filialId > 0 ? $filialId : null,
            'usuario_id'      => $usuarioId,
            'produto_id'      => $produtoId,
            'contexto'        => '',
            'tipo'            => $tipo,
            'quantidade'      => $quantidade,
            'origem_tipo'     => $origemTipo,
            'origem_id'       => $origemId,
            'saldo_momento'   => $saldoAtual,
            'idempotency_key' => $chaveUnica,
            'movimentado_em'  => $dataRegistro,
            'created_at'      => $dataRegistro, // Gravamos a mesma data para o Eloquent não sobrescrever
            'updated_at'      => date('Y-m-d H:i:s')
        ]);
    }

	public function pluStock($produto_id, $quantidade, $valor_unitario, $filial_id = null, $origem_tipo = null, $origem_id = null, $data_movimento = null)
    {
        $quantidade_positiva = abs($quantidade); 
        
        $estoque = \App\Models\Estoque::where('produto_id', $produto_id)
            ->when($filial_id > 0, function ($query) use ($filial_id) {
                return $query->where('filial_id', $filial_id);
            }, function ($query) {
                return $query->whereNull('filial_id');
            })->first();

        if (!$estoque) {
            \App\Models\Estoque::create([
                'empresa_id' => session('user_logged')['empresa_id'] ?? 1,
                'produto_id' => $produto_id,
                'filial_id' => $filial_id > 0 ? $filial_id : null,
                'quantidade' => $quantidade_positiva,
                'valor_compra' => $valor_unitario
            ]);
        } else {
            $estoque->quantidade += $quantidade_positiva; 
            $estoque->valor_compra = $valor_unitario;
            $estoque->save();
        }

        // Passa a data adiante
        $this->registarMovimentacao($produto_id, $quantidade_positiva, 'entrada', $origem_tipo, $origem_id, $filial_id, $data_movimento);
    }

    // 2. Adicionado o parâmetro de data aqui também
    public function downStock($produto_id, $quantidade, $filial_id = null, $origem_tipo = null, $origem_id = null, $data_movimento = null)
    {
        $quantidade_positiva = abs($quantidade);

        $estoque = \App\Models\Estoque::where('produto_id', $produto_id)
            ->when($filial_id > 0, function ($query) use ($filial_id) {
                return $query->where('filial_id', $filial_id);
            }, function ($query) {
                return $query->whereNull('filial_id');
            })->first();

        if ($estoque) {
            $estoque->quantidade -= $quantidade_positiva; 
            $estoque->save();
        }

        // Passa a data adiante
        $this->registarMovimentacao($produto_id, $quantidade_positiva, 'saida', $origem_tipo, $origem_id, $filial_id, $data_movimento);
    }
}