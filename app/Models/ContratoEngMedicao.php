<?php

namespace App\Models;

class ContratoEngMedicao extends BaseModel
{
    protected $table = 'contrato_eng_medicoes';

    protected $fillable = [
        'contrato_eng_id',
        'condicao_pagamento_id',
        'categoria_conta_id',
        'valor_total',
        'data_faturamento',
        'status_financeiro'
    ];

    // Relacionamento: Pertence a um Contrato
    public function contrato()
    {
        return $this->belongsTo(ContratoEngenharia::class, 'contrato_eng_id');
    }

    // Relacionamento: Possui vários itens (Mão de obra e Locação)
    public function itens()
    {
        return $this->hasMany(MedicaoEngItem::class, 'medicao_eng_id');
    }
}