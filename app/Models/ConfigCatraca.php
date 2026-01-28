<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigCatraca extends Model
{
    use HasFactory;

    protected $fillable = [ 'empresa_id', 'usuario_id', 'segundos_requisicao' ];
}
