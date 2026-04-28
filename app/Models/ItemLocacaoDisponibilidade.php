<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemLocacaoDisponibilidade extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'produto_id', 'data', 'locacao_id'
    ];
}
