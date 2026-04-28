<?php

namespace App\Models;


class EventoFuncionario extends BaseModel
{
    protected $fillable = [
		'evento_id', 'funcionario_id'
	];

	public function funcionario(){
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }
}
