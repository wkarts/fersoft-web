<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurtidaLoja extends Model
{
    use HasFactory;

    protected $fillable = [ 'empresa_id', 'cliente_id' ];
}
