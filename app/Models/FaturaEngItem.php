<?php

namespace App\Models;

class FaturaEngItem extends BaseModel
{
    protected $table = 'fatura_eng_itens';

    protected $fillable = [
        'fatura_eng_id', 'tipo_item', 'servico_id', 'produto_id', 'descricao',
        'quantidade', 'valor_unitario', 'sub_total', 'valor_total',
    ];

    protected $casts = [
        'quantidade' => 'decimal:4',
        'valor_unitario' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function fatura()
    {
        return $this->belongsTo(FaturaEngenharia::class, 'fatura_eng_id');
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
