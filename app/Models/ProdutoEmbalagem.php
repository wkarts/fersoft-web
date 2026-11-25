<?php

namespace App\Models;

class ProdutoEmbalagem extends BaseModel
{
    protected $table = 'produto_embalagens';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'produto_id',
        'unidade',
        'fator_conversao',
        'preco_venda',
        'codigo_barras'
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
