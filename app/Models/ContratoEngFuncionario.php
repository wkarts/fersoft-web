<?php

namespace App\Models;

class ContratoEngFuncionario extends BaseModel
{
    protected $table = 'contrato_eng_funcionarios';

    protected $fillable = [
        'contrato_eng_id', 'funcionario_id', 'data_alocacao', 'data_desalocacao', 'status',
    ];

    protected $casts = [
        'data_alocacao' => 'date',
        'data_desalocacao' => 'date',
    ];

    public function contrato()
    {
        return $this->belongsTo(ContratoEngenharia::class, 'contrato_eng_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }
}
