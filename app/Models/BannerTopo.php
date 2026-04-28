<?php

namespace App\Models;


class BannerTopo extends BaseModel
{
	protected $fillable = [
		'path', 'titulo', 'descricao', 'produto_delivery_id', 'pack_id', 'ativo'
	];

	public function produto(){
		return $this->belongsTo(ProdutoDelivery::class, 'produto_delivery_id');
	}

	public function pack(){
		return $this->belongsTo(PackProdutoDelivery::class, 'pack_id');
	}
}
