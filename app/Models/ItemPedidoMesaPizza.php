<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemPedidoMesaPizza extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'item_pedido', 'sabor_id'
    ];

    public function produto(){
        return $this->belongsTo(ProdutoDelivery::class, 'sabor_id');
    }

}
