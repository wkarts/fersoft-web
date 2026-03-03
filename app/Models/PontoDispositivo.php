<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PontoDispositivo extends Model
{
    protected $table = 'ponto_dispositivos';

    protected $guarded = [];

    protected $casts = [
        'ativo' => 'boolean',
        'ultimo_acesso_em' => 'datetime',
    ];
}
