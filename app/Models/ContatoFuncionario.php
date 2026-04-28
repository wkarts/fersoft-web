<?php

namespace App\Models;


class ContatoFuncionario extends BaseModel
{
	protected $fillable = [
		'nome', 'telefone', 'funcionario_id'
	];

	public function funcionario(){
		return $this->belongsTo(Funcionario::class, 'funcionario_id');
	}
}
