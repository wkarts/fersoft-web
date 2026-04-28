<?php

namespace App\Models;


class ListaComplementoDelivery extends BaseModel
{
    protected $fillable = [
		'categoria_id', 'complemento_id'
	];

	public function complemento(){
        return $this->belongsTo(ComplementoDelivery::class, 'complemento_id');
    }

    public function categoria(){
        return $this->belongsTo(CategoriaProdutoDelivery::class, 'categoria_id');
    }
}
