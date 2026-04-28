<?php

namespace App\Models;


class StockMovement extends BaseModel
{
    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'produto_id',
        'contexto',
        'tipo',
        'quantidade',
        'custo_unitario',
        'valor_total',
        'origem_tipo',
        'origem_id',
        'idempotency_key',
        'movimentado_em',
        'metadata',
    ];

    protected $casts = [
        'quantidade' => 'decimal:4',
        'custo_unitario' => 'decimal:6',
        'valor_total' => 'decimal:2',
        'usuario_id' => 'integer',
        'movimentado_em' => 'datetime',
        'metadata' => 'array',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
