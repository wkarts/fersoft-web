<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConfigCatraca extends BaseModel
{
    use HasFactory;

    protected $fillable = [ 'empresa_id', 'usuario_id', 'segundos_requisicao' ];
}
