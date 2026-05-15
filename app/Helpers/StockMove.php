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
			}, function ($query) {
                return $query->whereNull('filial_id');
            })
			->first();
	}

	
	private function registarMovimentacao($produtoId, $quantidade, $tipo, $origemTipo = null, $origemId = null, $filialId = null, $dataMovimento = null, $valorUnitario = 0) 
	{
		$estoque = $this->existStock($produtoId, $filialId);
		$saldoAtual = $estoque ? $estoque->quantidade : 0;
		
		$user_logged = session('user_logged');
		$usuarioId = $user_logged['id'] ?? (auth()->check() ? auth()->id() : null);
		
		// Define a empresa da sessão (Empresa 2)
		$empresa_id = $user_logged['empresa'] ?? $user_logged['empresa_id'] ?? 1;

		// LÓGICA DE DATA: Prioriza 100% a data informada por você
		if ($dataMovimento) {
			// Se você informou "2026-04-01", ele grava exatamente isso com a hora atual
			$dataRegistro = date('Y-m-d', strtotime($dataMovimento)) . ' ' . date('H:i:s');
		} else {
			$dataRegistro = date('Y-m-d H:i:s');
		}

		\Illuminate\Support\Facades\DB::table('stock_movements')->insert([
			'empresa_id'      => $empresa_id,
			'filial_id'       => $filialId > 0 ? $filialId : null,
			'usuario_id'      => $usuarioId,
			'produto_id'      => $produtoId,
			'contexto'        => $origemTipo == 'Ajuste' ? 'Ajuste Manual de Estoque' : '',
			'tipo'            => $tipo,
			'quantidade'      => $quantidade,
			'origem_tipo'     => $origemTipo,
			'origem_id'       => $origemId,
			'saldo_momento'   => $saldoAtual,
			'idempotency_key' => uniqid(date('YmdHis')),
			'movimentado_em'  => $dataRegistro, 
			'created_at'      => $dataRegistro, 
			'updated_at'      => date('Y-m-d H:i:s'),
			'custo_unitario'  => $valorUnitario, // AGORA A VARIÁVEL EXISTE!
			'valor_total'     => $quantidade * $valorUnitario
		]);
	}
  
	public function pluStock($produto_id, $quantidade, $valor_unitario, $filial_id = null, $origem_tipo = null, $origem_id = null, $data_movimento = null)
    {
        $quantidade_positiva = abs($quantidade); 

        // 1. Busca o estoque considerando se é Matriz (null) ou Filial (id)
        $estoque = $this->existStock($produto_id, $filial_id);

        if (!$estoque) {
            $p = Produto::findOrFail($produto_id);

            // CORREÇÃO: Pega a empresa do produto ou da sessão, nunca deixa "1" fixo
            $empresa_id = $p->empresa_id ?? session('user_logged')['empresa_id'];

            \App\Models\Estoque::create([
                'empresa_id'   => $empresa_id, 
                'produto_id'   => $produto_id,
                'filial_id'    => $filial_id > 0 ? $filial_id : null, // Garante NULL para Matriz
                'quantidade'   => $quantidade_positiva,
                'valor_compra' => $valor_unitario
            ]);
        } else {
            // 2. Atualiza estoque existente
            $estoque->quantidade += $quantidade_positiva; 
            $estoque->valor_compra = $valor_unitario;
            $estoque->save();
        }

        // 3. Registra no histórico (tabela stock_movements)
        // Certifique-se que o método registarMovimentacao use a mesma lógica de empresa!
        $this->registarMovimentacao($produto_id, $quantidade_positiva, 'entrada', $origem_tipo, $origem_id, $filial_id, $data_movimento, $valor_unitario);
    }

    public function downStock($produto_id, $quantidade, $filial_id = null, $origem_tipo = null, $origem_id = null, $data_movimento = null)
    {
        $quantidade_positiva = abs($quantidade);
        $estoque = $this->existStock($produto_id, $filial_id);
      	$valor_unitario = $estoque ? $estoque->valor_compra : 0;

        if ($estoque) {
            $estoque->quantidade -= $quantidade_positiva; 
            $estoque->save();
        }

        $this->registarMovimentacao($produto_id, $quantidade_positiva, 'saida', $origem_tipo, $origem_id, $filial_id, $data_movimento, $valor_unitario);
    }
}