<?php

namespace App\Models;


class ItemPedidoEcommerce extends BaseModel
{
    protected $fillable = [
		'pedido_id', 'produto_id', 'quantidade'
	];

	public function produto(){
		return $this->belongsTo(ProdutoEcommerce::class, 'produto_id');
	}
	
}
