<?php

namespace App\Models;


class CurtidaProdutoEcommerce extends BaseModel
{
    protected $fillable = [
        'produto_id', 'cliente_id'
    ];

    public function produto(){
        return $this->belongsTo(ProdutoEcommerce::class, 'produto_id');
    }
}
