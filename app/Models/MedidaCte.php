<?php

namespace App\Models;


class MedidaCte extends BaseModel
{
    protected $fillable = [
        'cte_id', 'tipo_medida', 'quantidade_carga', 'cod_unidade'
    ];
}
