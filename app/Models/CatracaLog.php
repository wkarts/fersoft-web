<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatracaLog extends Model
{
    use HasFactory;

    protected $fillable = [ 'empresa_id', 'comanda', 'tipo' ];
}
