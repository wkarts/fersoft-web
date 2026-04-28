<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class NFeReferecia extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'venda_id', 'chave'
    ];
}
