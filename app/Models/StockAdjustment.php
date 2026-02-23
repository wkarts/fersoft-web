<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
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
