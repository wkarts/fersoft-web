<?php

namespace App\Models;


class ItemCotacao extends BaseModel
{
    protected $fillable = [
        'produto_id', 'cotacao_id', 'quantidade', 'valor', 'valor_unitario'
    ];

    public function cotacao(){
		return $this->belongsTo(Cotacao::class, 'cotacao_id');
	}

	public function produto(){
		return $this->belongsTo(Produto::class, 'produto_id');
	}
}
