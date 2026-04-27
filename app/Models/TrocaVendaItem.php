<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrocaVendaItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'troca_id', 'produto_id', 'valor', 'quantidade'
    ];
}
