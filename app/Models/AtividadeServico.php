<?php

namespace App\Models;


class AtividadeServico extends BaseModel
{
    protected $fillable = [
		'servico_id', 'atividade_id'
	];

	public function servico(){
        return $this->belongsTo(Servico::class, 'servico_id');
    }
}
