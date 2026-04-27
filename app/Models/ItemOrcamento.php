<?php

namespace App\Models;


class ItemOrcamento extends BaseModel
{
    protected $fillable = [
		'produto_id', 'orcamento_id', 'quantidade', 'valor', 'altura', 'largura', 'profundidade',
        'acrescimo_perca', 'esquerda', 'direita', 'inferior', 'superior'
	];

	public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function orcamento(){
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }


}
