<?php

namespace App\Models;


class Marca extends BaseModel
{
    protected $fillable = [
        'nome', 'empresa_id'
    ];

    public function produtos(){
        return $this->hasMany('App\Models\Produto', 'marca_id', 'id');
    }
    
}
