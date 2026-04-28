<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemPedidoMesaComplemento extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'item_pedido_id', 'complemento_id', 'quantidade'
    ];

    public function adicional(){
        return $this->belongsTo(ComplementoDelivery::class, 'complemento_id');
    }
}
