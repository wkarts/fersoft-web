<?php

namespace App\Models;


class FaturaOrcamento extends BaseModel
{
	protected $fillable = [
		'valor', 'vencimento', 'orcamento_id', 'empresa_id', 'tipo_pagamento'
	];

	public function orcamento(){
		return $this->belongsTo(Orcamento::class, 'orcamento_id');
	}

}
