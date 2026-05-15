<?php
namespace App\Models;

class TabelaPrecoItem extends BaseModel
{
    protected $table = 'tabela_preco_itens';
    
    // Liga o item com o cadastro de produtos
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}