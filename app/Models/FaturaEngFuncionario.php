<?php

namespace App\Models;

class FaturaEngFuncionario extends BaseModel
{
    protected $table = 'fatura_eng_funcionarios';

    protected $fillable = [
        'fatura_eng_id', 'funcionario_id', 'funcao', 'diarias', 'valor_diaria', 'valor_total',
    ];

    protected $casts = [
        'diarias' => 'decimal:2',
        'valor_diaria' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function fatura()
    {
        return $this->belongsTo(FaturaEngenharia::class, 'fatura_eng_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }
}
