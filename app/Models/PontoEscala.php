<?php

namespace App\Models;


class PontoEscala extends BaseModel
{
    protected $table = 'ponto_escalas';

    protected $guarded = [];

    protected $casts = [
        'regras' => 'array',
        'ativo' => 'boolean',
        'vigencia_inicio' => 'date',
        'vigencia_fim' => 'date',
    ];
}
