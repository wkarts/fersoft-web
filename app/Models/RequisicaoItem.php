<?php

namespace App\Models;


class RequisicaoItem extends BaseModel
{
    protected $table = 'requisicao_itens';

    protected $fillable = [
        'fabricante',
        'empresa_id', 
        'filial_id', 
        'usuario_id', 
        'requisicao_id', 
        'produto_id', 
        'quantidade', 
        'ca_snapshot',
    	'fab_snapshot'
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}