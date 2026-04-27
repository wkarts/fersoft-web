<?php

namespace App\Models;


class PontoAfdRegistro extends BaseModel
{
    protected $table = 'ponto_afd_registros';

    protected $guarded = [];

    protected $casts = [
        'dados_parseados' => 'array',
        'inconsistente' => 'boolean',
        'data_hora_marcacao' => 'datetime',
    ];
}
