<?php

namespace App\Models;


class ItemPedidoComplementoLocal extends BaseModel
{
    protected $fillable = [
		'item_pedido', 'complemento_id', 'quantidade'
	];

	public function adicional(){
        return $this->belongsTo(ComplementoDelivery::class, 'complemento_id');
    }
}
