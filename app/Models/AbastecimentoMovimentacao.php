<?php

namespace App\Models;


class AbastecimentoMovimentacao extends BaseModel
{
    protected $table = 'abastecimentos_movimentacoes';

    protected $fillable = [
        'movimentacao_id', 
        'produto_id', 
        'tipo', 
        'quantidade', 
        'valor_unitario', 
        'valor_total', 
        'km_abastecimento',
        'data_abastecimento',
		'usuario_id',
		'filial_id',
		'empresa_id'
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function movimentacao()
    {
        return $this->belongsTo(MovimentacaoVeiculo::class, 'movimentacao_id');
    }
}