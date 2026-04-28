<?php

namespace App\Models;


class PontoTurno extends BaseModel
{
    protected $table = 'ponto_turnos';

    protected $guarded = [];

    protected $casts = [
        'cruza_meia_noite' => 'boolean',
        'ativo' => 'boolean',
    ];
}
