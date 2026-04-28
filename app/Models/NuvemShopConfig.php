<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class NuvemShopConfig extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'client_id', 'client_secret', 'email', 'empresa_id', 'natureza_padrao', 'forma_pagamento_padrao'
    ];
}
