<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MovimentacaoFinanceira extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'conta_id', 'tabela', 'status', 'valor'
    ];
}
