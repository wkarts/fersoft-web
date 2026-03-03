<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PontoImportacaoLog extends Model
{
    protected $table = 'ponto_importacao_logs';

    protected $guarded = [];

    protected $casts = [
        'contexto' => 'array',
    ];
}
