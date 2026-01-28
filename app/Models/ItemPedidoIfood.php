<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPedidoIfood extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id', 'nome_produto', 'image_url', 'unidade', 'valor_unitario', 'quantidade', 'total',
        'valor_adicional', 'observacao', 'produto_id'
    ];

    public function adicionais(){
        return $this->hasMany(AdicionalItemPedidoIfood::class, 'item_pedido_id');
    }

    public function produto(){
        return $this->belongsTo(ProdutoIfood::class, 'produto_id');
    }


}
