<?php

namespace App\Models;


class AdiantamentoMovimentacao extends BaseModel
{
    protected $table = 'adiantamento_movimentacoes';
    protected $fillable = [
        'adiantamento_id',
        'conta_receber_id',
        'conta_pagar_id',
        'empresa_id',
        'filial_id',
        'usuario_id',
        'tipo',
        'valor',
        'data',
        'created_at',
        'updated_at',
    ];

    public function adiantamento()
    {
        return $this->belongsTo(Adiantamento::class);
    }
}