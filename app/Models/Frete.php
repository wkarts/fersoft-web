<?php

namespace App\Models;


class Frete extends BaseModel
{
    protected $fillable = [
        'valor', 'placa', 'tipo', 'uf', 'numeracaoVolumes', 'peso_liquido', 'peso_bruto',
        'especie', 'qtdVolumes'
    ];
}
