<?php

namespace App\Models;


class CarrosselEcommerce extends BaseModel
{
	protected $fillable = [ 
		'empresa_id', 'titulo', 'descricao', 'img', 'link_acao', 'nome_botao', 'cor_titulo',
		'cor_descricao'
	];

	protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!empty($this->img)) {
            $image_url = asset('/ecommerce/carrossel/' . rawurlencode($this->img));
        } else {
            $image_url = asset('/img/default.png');
        }
        return $image_url;
    }
}
