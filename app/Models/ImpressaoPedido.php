<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpressaoPedido extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'empresa_id', 'impressora_id', 'produto_id', 'quantidade_item',
        'valor_total', 'tabela', 'status', 'pedido_id'
    ];

    public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }

}
