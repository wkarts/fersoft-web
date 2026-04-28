<?php

namespace App\Models;


class Remessa extends BaseModel
{
    protected $fillable = [
        'nome_arquivo', 'empresa_id'
    ];

    public function boletos(){
        return $this->hasMany('App\Models\RemessaBoleto', 'remessa_id', 'id');
    }
}
