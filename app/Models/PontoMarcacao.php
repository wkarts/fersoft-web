<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PontoMarcacao extends Model
{
    protected $guarded = [];

    protected $table = 'ponto_marcacoes';

    protected $casts = [
        'dados_brutos' => 'array',
        'inconsistente' => 'boolean',
        'data_hora_marcacao' => 'datetime',
    ];
}
