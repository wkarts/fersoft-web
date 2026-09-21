<?php

namespace App\Models;

class MtrResiduo extends BaseModel
{
    protected $table = 'mtr_residuos';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'res_codigo',
        'res_codigo_ibama', 'res_descricao', 'grr_descricao',
        'grr_representacao',
    ];
}
