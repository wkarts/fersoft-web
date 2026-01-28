<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVendaCaixaPreVenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'produto_id', 'venda_caixa_prevenda_id', 'quantidade', 'valor', 'item_pedido_id', 
        'observacao', 'cfop'
    ];

    public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
