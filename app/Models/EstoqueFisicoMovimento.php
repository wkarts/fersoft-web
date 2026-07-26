<?php

namespace App\Models;

class EstoqueFisicoMovimento extends BaseModel
{
    protected $table = 'estoque_fisico_movimentos';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'produto_id',
        'pesagem_id',
        'peso_bruto',
        'peso_impureza',
        'tipo',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'data_movimento',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'filial_id' => 'integer',
        'usuario_id' => 'integer',
        'produto_id' => 'integer',
        'pesagem_id' => 'integer',
        'peso_bruto' => 'decimal:2',
        'peso_impureza' => 'decimal:2',
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:4',
        'valor_total' => 'decimal:2',
        'data_movimento' => 'date',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function pesagem()
    {
        return $this->belongsTo(Pesagem::class, 'pesagem_id');
    }
}
