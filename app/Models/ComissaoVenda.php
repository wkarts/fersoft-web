<?php

namespace App\Models;


class ComissaoVenda extends BaseModel
{
	protected $fillable = [
		'funcionario_id', 'venda_id', 'tabela', 'valor', 'status', 'empresa_id'
	];

	public function funcionario(){
		return $this->belongsTo(Funcionario::class, 'funcionario_id');
	}

	public function valodDaVenda(){
		if($this->tabela == 'vendas'){
			return $this->belongsTo(Venda::class, 'venda_id');
		}else{
			return $this->belongsTo(VendaCaixa::class, 'venda_id');
		}
	}

	public function tipo(){
		if($this->tabela == 'vendas'){
			return 'Venda';
		}elseif($this->tabela == 'balcao'){
			return 'Balcão';
		}else{
			return 'PDV';
		}
	}
}
