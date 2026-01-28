<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPedidoMesa extends Model
{
    use HasFactory;

    protected $fillable = [
        'produto_id', 'pedido_id', 'status', 'quantidade', 'valor', 'observacao', 'tamanho_id'
    ];


    public function produto(){
        return $this->belongsTo(ProdutoDelivery::class, 'produto_id')->with('produto')->with('categoria');
    }

    public function tamanho(){
        return $this->belongsTo(TamanhoPizza::class, 'tamanho_id');
    }

    public function itensAdicionais(){
        return $this->hasMany(ItemPedidoMesaComplemento::class, 'item_pedido_id', 'id')->with('adicional');
    }

    public function sabores(){
        return $this->hasMany(ItemPedidoMesaPizza::class, 'item_pedido', 'id');
    }
}
