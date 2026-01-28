<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliacaoDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id', 'empresa_id', 'cliente_id', 'descricao_pedido', 
        'observacao_cliente', 'nota'
    ];

    public function pedido(){
        return $this->belongsTo(PedidoDelivery::class, 'pedido_id');
    }

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id')->with('deliveryConfig');
    }

    public function cliente(){
        return $this->belongsTo(ClienteDelivery::class, 'cliente_id');
    }

}
