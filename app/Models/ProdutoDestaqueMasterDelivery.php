<?php

namespace App\Models;


class ProdutoDestaqueMasterDelivery extends BaseModel
{
	protected $fillable = [ 'produto_id', 'categoria_id' ];

	public function produto(){
		return $this->belongsTo(ProdutoDelivery::class, 'produto_id');
	}
}
