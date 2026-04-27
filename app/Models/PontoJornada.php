<?php

namespace App\Models;


class PontoJornada extends BaseModel
{
    protected $table = 'ponto_jornadas';

    protected $guarded = [];

    protected $casts = [
        'regras_semana' => 'array',
        'ativo' => 'boolean',
    ];
}
