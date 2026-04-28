<?php

namespace App\Models;


class StockDailyAggregate extends BaseModel
{
    protected $fillable = [
        'data_ref',
        'empresa_id',
        'filial_id',
        'produto_id',
        'contexto',
        'entrada',
        'saida',
        'saldo',
        'valor_entrada',
        'valor_saida',
        'custo_medio',
    ];

    protected $casts = [
        'data_ref' => 'date:Y-m-d',
        'entrada' => 'decimal:4',
        'saida' => 'decimal:4',
        'saldo' => 'decimal:4',
        'valor_entrada' => 'decimal:2',
        'valor_saida' => 'decimal:2',
        'custo_medio' => 'decimal:6',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
