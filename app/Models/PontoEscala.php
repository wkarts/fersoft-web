<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PontoEscala extends Model
{
    protected $guarded = [];

    protected $casts = [
        'regras' => 'array',
        'ativo' => 'boolean',
        'vigencia_inicio' => 'date',
        'vigencia_fim' => 'date',
    ];
}
