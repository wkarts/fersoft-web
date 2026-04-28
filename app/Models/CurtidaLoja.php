<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurtidaLoja extends BaseModel
{
    use HasFactory;

    protected $fillable = [ 'empresa_id', 'cliente_id' ];
}
