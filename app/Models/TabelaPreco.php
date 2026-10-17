<?php
namespace App\Models;

class TabelaPreco extends BaseModel
{
    protected $table = 'tabelas_precos';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'descricao',
        'tipo_ajuste',
        'percentual_ajuste',
        'status',
        'created_at',
        'updated_at',
    ];
    
    // Liga a tabela com os itens dela
    public function itens()
    {
        return $this->hasMany(TabelaPrecoItem::class, 'tabela_preco_id');
    }
}