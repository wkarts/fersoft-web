<?php

namespace App\Models;


class PontoMarcacao extends BaseModel
{
    protected $guarded = [];

    protected $table = 'ponto_marcacoes';

    protected $casts = [
        'dados_brutos' => 'array',
        'inconsistente' => 'boolean',
        'data_hora_marcacao' => 'datetime',
    ];
}
