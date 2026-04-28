<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompraReferencia extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'compra_id', 'chave'
    ];
}
