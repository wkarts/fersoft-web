<?php

namespace App\Models;


class Contrato extends BaseModel
{
    protected $fillable = [
        'texto', 'accessos_forcar_assinar', 'usar_certificado'
    ];
}
