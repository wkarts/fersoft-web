<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $fillable = [
        'texto', 'accessos_forcar_assinar', 'usar_certificado'
    ];
}
