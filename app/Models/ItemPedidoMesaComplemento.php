<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPedidoMesaComplemento extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_pedido_id', 'complemento_id', 'quantidade'
    ];

    public function adicional(){
        return $this->belongsTo(ComplementoDelivery::class, 'complemento_id');
    }
}
