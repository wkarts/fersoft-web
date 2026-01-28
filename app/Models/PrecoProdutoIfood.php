<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrecoProdutoIfood extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_ifood', 'produto_ifood_id', 'valor'
    ];
}
