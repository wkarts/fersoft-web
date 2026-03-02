<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PontoJornada extends Model
{
    protected $guarded = [];

    protected $casts = [
        'regras_semana' => 'array',
        'ativo' => 'boolean',
    ];
}
