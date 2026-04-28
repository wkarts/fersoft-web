<?php

namespace App\Models;


class PedidoQrCodeCliente extends BaseModel
{
    protected $fillable = [
        'pedido_id', 'hash'
    ];

}
