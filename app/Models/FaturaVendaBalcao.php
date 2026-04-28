<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FaturaVendaBalcao extends BaseModel
{
    protected $fillable = [
        'valor', 'forma_pagamento', 'venda_balcao_id', 'data_vencimento'
    ];
}
