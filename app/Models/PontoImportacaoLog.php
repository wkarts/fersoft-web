<?php

namespace App\Models;


class PontoImportacaoLog extends BaseModel
{
    protected $table = 'ponto_importacao_logs';

    protected $guarded = [];

    protected $casts = [
        'contexto' => 'array',
    ];
}
