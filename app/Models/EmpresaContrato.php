<?php

namespace App\Models;


class EmpresaContrato extends BaseModel
{
    protected $fillable = [
        'empresa_id', 'status', 'cpf_cnpj'
    ];
}
