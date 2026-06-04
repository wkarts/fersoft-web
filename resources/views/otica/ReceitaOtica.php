<?php

namespace App\Models;

class ReceitaOtica extends BaseModel
{
    protected $table = 'cliente_oticas';

    // A MÁGICA ESTÁ AQUI: Todos os campos libertados para o Laravel gravar!
    protected $fillable = [
        'empresa_id', 'filial_id', 'cliente_id', 'lente_id', 'armacao_id', 
        'status', 'valor_lente', 'valor_armacao', 'medico', 'venda_id',
        'armacao', 'lente', 'tipo_lente', 'tratamento', 'forma_pagamento',
        'previsao_retorno_dias', 'observacao', 'data', 'data_entrega',
        'qtd_armacao', 'qtd_lente',

        // Receita Longe
        'esf_od_longe', 'cil_od_longe', 'eixo_od_longe', 'dnp_od_longe', 'dp_od_longe',
        'esf_oe_longe', 'cil_oe_longe', 'eixo_oe_longe', 'dnp_oe_longe', 'dp_oe_longe',

        // Receita Perto
        'esf_od_perto', 'cil_od_perto', 'eixo_od_perto', 'adicao_od_perto', 'altura_od_perto',
        'esf_oe_perto', 'cil_oe_perto', 'eixo_oe_perto', 'adicao_oe_perto', 'altura_oe_perto'
    ];

    public function cliente() {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function produtoLente() {
        return $this->belongsTo(Produto::class, 'lente_id');
    }

    public function produtoArmacao() {
        return $this->belongsTo(Produto::class, 'armacao_id');
    }
}