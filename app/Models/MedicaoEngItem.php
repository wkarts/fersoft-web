<?php

namespace App\Models;

class ContratoEngItem extends BaseModel
{
    protected $table = 'contrato_eng_itens';

    protected $fillable = [
        'contrato_eng_id', 'tipo_item', 'servico_id', 'produto_id', 
        'quantidade_prevista', 'valor_unitario', 'valor_total'
    ];

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}