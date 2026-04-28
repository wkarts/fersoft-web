<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventoSalario extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'nome', 'tipo', 'metodo', 'condicao', 'ativo', 'empresa_id', 'tipo_valor'
    ];
}
