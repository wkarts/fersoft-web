<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContaFinanceira extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'categoria_id', 'sub_categoria_id', 'nome', 'saldo_inicial'
    ];
}
