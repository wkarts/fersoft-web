<?php

namespace App\Models;

class MtrDeparaResiduo extends BaseModel
{
    protected $table = 'mtr_depara_residuos';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'orgao', 'produto_id',
        'categoria_id', 'sub_categoria_id', 'ncm', 'cod_ibama',
        'descricao_residuo', 'classe_residuo', 'estado_fisico',
        'acondicionamento_id', 'tratamento_id', 'unidade_medida',
        'fator_conversao',
    ];

    protected $casts = [
        'fator_conversao' => 'decimal:4',
    ];
}
