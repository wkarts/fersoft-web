<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaturaVendaBalcao extends Model
{
    protected $fillable = [
        'valor', 'forma_pagamento', 'venda_balcao_id', 'data_vencimento'
    ];
}
