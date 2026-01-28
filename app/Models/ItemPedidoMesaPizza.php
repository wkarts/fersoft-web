<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPedidoMesaPizza extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_pedido', 'sabor_id'
    ];

    public function produto(){
        return $this->belongsTo(ProdutoDelivery::class, 'sabor_id');
    }

}
