<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdicionalItemPedidoIfood extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'item_pedido_id', 'nome', 'unidade', 'quantidade', 'valor_unitario', 'total'
    ];
}
