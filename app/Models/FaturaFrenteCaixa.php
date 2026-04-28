<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FaturaFrenteCaixa extends BaseModel
{
    use HasFactory;
    protected $fillable = [
		'valor', 'forma_pagamento', 'venda_caixa_id', 'entrada', 'data_vencimento'
	];

	public function venda(){
		return $this->belongsTo(VendaCaixa::class, 'venda_caixa_id');
	}
}
