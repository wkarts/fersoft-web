<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatracaLog extends BaseModel
{
    use HasFactory;

    protected $fillable = [ 'empresa_id', 'comanda', 'tipo' ];
}
