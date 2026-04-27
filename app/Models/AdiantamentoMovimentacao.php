<?php

namespace App\Models;


class AdiantamentoMovimentacao extends BaseModel
{
    protected $table = 'adiantamento_movimentacoes';
    protected $fillable = ['adiantamento_id', 'conta_receber_id', 'conta_pagar_id', 'valor', 'data'];

    public function adiantamento()
    {
        return $this->belongsTo(Adiantamento::class);
    }
}