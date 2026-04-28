<?php

namespace App\Models;


class ListaPreco extends BaseModel
{
    protected $fillable = [
		'nome', 'percentual_alteracao', 'empresa_id', 'tipo', 'tipo_inc_red'
	];

	public function itens(){
        return $this->hasMany('App\Models\ProdutoListaPreco', 'lista_id', 'id');
    }
}
