<?php

namespace App\Models;


class CategoriaMasterDelivery extends BaseModel
{
    protected $fillable = [ 'nome', 'img' ];

    protected $appends = [
        'img_url'
    ];

    public function getImgUrlAttribute()
    {
        return env("PATH_URL") . "/categorias_delivery/" . $this->img;
    }

    // public function produtos(){
    //     return $this->hasMany('App\Models\ProdutoDestaqueMasterDelivery', 'categoria_id', 'id');
    // }
}
