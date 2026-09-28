<?php

namespace App\Models;

class ProdutoEmbalagem extends BaseModel
{
    // Dizendo ao Laravel o nome exato da tabela no banco
    protected $table = 'produto_embalagens';

    protected $fillable = [
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
