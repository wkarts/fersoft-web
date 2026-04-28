<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContaFinanceira extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'categoria_id', 'sub_categoria_id', 'nome', 'saldo_inicial'
    ];
}
