<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PontoTurno extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cruza_meia_noite' => 'boolean',
        'ativo' => 'boolean',
    ];
}
