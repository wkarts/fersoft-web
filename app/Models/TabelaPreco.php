<?php
namespace App\Models;

class TabelaPreco extends BaseModel
{
    protected $table = 'tabelas_precos';
    
    // Liga a tabela com os itens dela
    public function itens()
    {
        return $this->hasMany(TabelaPrecoItem::class, 'tabela_preco_id');
    }
}