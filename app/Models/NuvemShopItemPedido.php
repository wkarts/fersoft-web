<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class NuvemShopItemPedido extends BaseModel
{
    use HasFactory;

    protected $fillable = [ 'pedido_id', 'produto_id', 'quantidade', 'valor', 'nome' ];

    public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
