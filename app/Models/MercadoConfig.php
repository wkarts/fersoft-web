<?php

namespace App\Models;


class MercadoConfig extends BaseModel
{
    protected $fillable = [
        'email', 'funcionamento', 'descricao', 'total_de_produtos', 'total_de_clientes', 'total_de_funcionarios'
    ];
}
