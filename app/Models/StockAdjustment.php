<?php

namespace App\Models;


class StockAdjustment extends BaseModel
{
    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'data_ref',
        'observacao',
        'itens',
    ];

    protected $casts = [
        'data_ref' => 'date:Y-m-d',
        'itens' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
