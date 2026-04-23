<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DespesaMovimentacao extends BaseModel
{
    // Nome da tabela que você criou no banco de dados
    protected $table = 'despesas_movimentacoes';

    // Campos que o sistema pode preencher automaticamente
    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'movimentacao_id',
        'tipo',
        'valor',
        'descricao'
    ];

    // Relacionamento reverso: uma despesa pertence a uma movimentação
    public function movimentacao()
    {
        return $this->belongsTo(MovimentacaoVeiculo::class, 'movimentacao_id');
    }
}
