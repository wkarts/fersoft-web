<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdicionalItemPedidoIfood extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_pedido_id', 'nome', 'unidade', 'quantidade', 'valor_unitario', 'total'
    ];
}
