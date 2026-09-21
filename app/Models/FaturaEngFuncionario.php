<?php

namespace App\Models;

class FaturaEngFuncionario extends BaseModel
{
    protected $table = 'fatura_eng_funcionarios';

    protected $fillable = [
        'fatura_eng_id',
        'funcionario_id',
        'funcao',
        'diarias',
        'valor_diaria',
        'valor_total'
    ];

    public function fatura()
    {
        return $this->belongsTo(FaturaEngenharia::class, 'fatura_eng_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id'); // Ajuste o nome do Model de funcionários do seu ERP se necessário
    }
}