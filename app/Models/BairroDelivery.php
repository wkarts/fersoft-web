<?php

namespace App\Models;


class BairroDelivery extends BaseModel
{
    protected $fillable = [
        'nome', 'valor_entrega', 'cidade_id'
    ];

    public function cidade(){
        return $this->belongsTo(CidadeDelivery::class, 'cidade_id');
    }
}
