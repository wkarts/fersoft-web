<?php

namespace App\Models;


class AdiantamentoMovimentacao extends BaseModel
{
    protected $table = 'adiantamento_movimentacoes';
    protected $fillable = [
    						'adiantamento_id', 
    						'valor', 
    						'data',
    						'tipo', 
    						'conta_pagar_id', 
    						'conta_receber_id', 
    						'usuario_id', 
    						'empresa_id', 
    						'created_at', 
    						'updated_at'
    					   ];
    					   
    public function adiantamento()
    {
        return $this->belongsTo(Adiantamento::class);
    }
}