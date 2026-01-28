<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceiroIndeterminado extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'valor', 'data_pagamento'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

}
