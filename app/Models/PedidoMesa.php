<?php

namespace App\Models;

use App\Traits\FilialInjectable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoMesa extends BaseModel
{
    use HasFactory, FilialInjectable;

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'valor_total',
        'forma_pagamento',
        'observacao',
        'estado',
        'uid',
        'nome_cliente',
        'telefone_cliente',
        'mesa_id'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial(){
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function mesa(){
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }

    public function itens(){
        return $this->hasMany(ItemPedidoMesa::class, 'pedido_id', 'id')->with('produto')->with('itensAdicionais')
        ->with('tamanho');
    }

    public function somaItens(){
        $total = 0;
        foreach($this->itens as $item){
            $total += $item->quantidade * $item->valor;
        }
        return $total;
    }
}
