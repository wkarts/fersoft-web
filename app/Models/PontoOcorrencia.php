<?php

namespace App\Models;


class PontoOcorrencia extends BaseModel
{
    protected $table = 'ponto_ocorrencias';

    protected $guarded = [];

    protected $casts = [
        'dados' => 'array',
        'data_referencia' => 'date',
    ];
}
