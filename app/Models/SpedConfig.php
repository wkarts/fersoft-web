<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpedConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'codigo_conta_analitica', 'codigo_receita', 'gerar_bloco_k', 'layout_bloco_k'
    ];
}
