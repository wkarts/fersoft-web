<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoriaIfood extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'nome', 'status', 'id_ifood'
    ];
}
