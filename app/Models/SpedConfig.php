<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpedConfig extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'codigo_conta_analitica', 'codigo_receita', 'gerar_bloco_k', 'layout_bloco_k'
    ];
}
