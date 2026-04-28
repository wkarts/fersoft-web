<?php

namespace App\Models;


class Motoboy extends BaseModel
{
    protected $fillable = [
        'nome', 'celular', 'rua', 'numero', 'bairro', 'status', 'empresa_id', 'valor_entrega_padrao'
    ];

}
