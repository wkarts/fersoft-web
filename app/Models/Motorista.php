<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Motorista extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'nome', 'cpf', 'empresa_id'
    ];
}
