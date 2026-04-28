<?php

namespace App\Models;


class TamanhoPizza extends BaseModel
{
	protected $fillable = [ 
		'nome', 'pedacos', 'maximo_sabores', 'empresa_id'
	];

	public function produtoPizza(){
		return $this->hasMany('App\Models\ProdutoPizza', 'tamanho_id', 'id');
	}

	public function nome(){
		$temp = explode("_", $this->nome);
		if(sizeof($temp) > 1){
			$t = "";
			foreach($temp as $tp){
				$t .= $tp . " ";
			}
			return $t;
		}else{
			return $this->nome;
		}
	}
}
