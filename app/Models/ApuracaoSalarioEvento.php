<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApuracaoSalarioEvento extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'apuracao_id', 'evento_id', 'valor', 'metodo', 'condicao', 'nome'
    ];
}
