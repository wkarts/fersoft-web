<?php

namespace App\Models;


class RequisicaoItem extends BaseModel
{
    protected $table = 'requisicao_itens';

    protected $fillable = [
        'requisicao_id', 
        'produto_id', 
        'quantidade'
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}