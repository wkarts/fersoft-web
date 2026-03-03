<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PontoAfdRegistro extends Model
{
    protected $table = 'ponto_afd_registros';

    protected $guarded = [];

    protected $casts = [
        'dados_parseados' => 'array',
        'inconsistente' => 'boolean',
        'data_hora_marcacao' => 'datetime',
    ];
}
