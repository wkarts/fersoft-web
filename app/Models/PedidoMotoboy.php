<?php

namespace App\Models;

use App\Traits\FilialInjectable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoMotoboy extends BaseModel
{
    use HasFactory, FilialInjectable;

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'motoboy_id',
        'pedido_id',
        'valor',
        'status_pagamento'
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

    public function motoboy(){
		return $this->belongsTo(Motoboy::class, 'motoboy_id');
	}

	public function pedido(){
		return $this->belongsTo(PedidoDelivery::class, 'pedido_id');
	}
}
