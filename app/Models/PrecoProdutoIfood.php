<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrecoProdutoIfood extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'id_ifood', 'produto_ifood_id', 'valor'
    ];
}
