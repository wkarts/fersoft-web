<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motoboy extends Model
{
    protected $fillable = [
        'nome', 'celular', 'rua', 'numero', 'bairro', 'status', 'empresa_id', 'valor_entrega_padrao'
    ];

}
