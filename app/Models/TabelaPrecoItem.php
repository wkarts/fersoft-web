<?php
namespace App\Models;

class TabelaPrecoItem extends BaseModel
{
    protected $table = 'tabela_preco_itens';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'tabela_preco_id',
        'produto_id',
        'tipo_frete',
        'valor_kg',
        'created_at',
        'updated_at',
    ];
    
    // Liga o item com o cadastro de produtos
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}