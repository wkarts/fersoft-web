<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinanceiroIndeterminado extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'valor', 'data_pagamento'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

}
