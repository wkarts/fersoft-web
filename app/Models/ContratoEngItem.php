<?php

namespace App\Models;

class ContratoEngItem extends BaseModel
{
    protected $table = 'contrato_eng_itens';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'contrato_eng_id', 'tipo_item', 'servico_id', 'produto_id',
        'quantidade_prevista', 'valor_unitario', 'valor_total',
    ];

    protected $casts = [
        'quantidade_prevista' => 'decimal:4',
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function contrato()
    {
        return $this->belongsTo(ContratoEngenharia::class, 'contrato_eng_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
