<?php

namespace App\Models;


class ImagensProdutoDelivery extends BaseModel
{	
	protected $fillable = [
		'produto_id', 'path'
	];

	protected $appends = [
		'img'
	];

	public function getImgAttribute()
	{
		return env("PATH_URL") . "/imagens_produtos/" . $this->path;
	}

	public function produto(){
        return $this->hasOne('App\Models\ProdutoDelivery', 'id', 'produto_id');
    }
	
}
