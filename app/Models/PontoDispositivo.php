<?php

namespace App\Models;


class PontoDispositivo extends BaseModel
{
    protected $table = 'ponto_dispositivos';

    protected $guarded = [];

    protected $casts = [
        'ativo' => 'boolean',
        'ultimo_acesso_em' => 'datetime',
    ];
}
