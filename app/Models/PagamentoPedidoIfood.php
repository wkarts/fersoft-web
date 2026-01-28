<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagamentoPedidoIfood extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id', 'forma_pagamento', 'tipo_pagamento', 'bandeira_cartao', 'valor'
    ];

    public static function getFormPay($form){
        $data = [
            'CASH' => 'Dinheiro',
            'CREDIT' => 'Crédito',
            'DEBIT' => 'Débito',
        ];
        if(isset($data[$form])){
            return $data[$form];
        }
        return $form;
    }
}
