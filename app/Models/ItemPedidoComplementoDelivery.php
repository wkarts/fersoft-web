<?php

namespace App\Models;


class ItemPedidoComplementoDelivery extends BaseModel
{
    protected $fillable = [
		'item_pedido_id', 'complemento_id', 'quantidade'
	];

	public function adicional(){
        return $this->belongsTo(ComplementoDelivery::class, 'complemento_id');
    }
}
