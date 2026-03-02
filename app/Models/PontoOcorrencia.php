<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PontoOcorrencia extends Model
{
    protected $guarded = [];

    protected $casts = [
        'dados' => 'array',
        'data_referencia' => 'date',
    ];
}
