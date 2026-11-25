<?php
namespace App\Models;

class TabelaPreco extends BaseModel
{
    protected $table = 'tabelas_preco';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'nome',
    ];

    // Faz com que $tabela->descricao retorne o valor da coluna 'nome' automaticamente nas Views
    public function getDescricaoAttribute()
    {
        return $this->attributes['nome'] ?? $this->attributes['descricao'] ?? null;
    }

    // Liga a tabela com os itens dela
    public function itens()
    {
        return $this->hasMany(TabelaPrecoItem::class, 'tabela_preco_id');
    }
}
