<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Locacao extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'fornecedor_id',
        'material_previsto',
        'finalidade',
        'contrato_id',
        'tipo',
        'inicio',
        'fim',
        'data_entrega',
        'data_solicitacao_retirada',
        'data_retirada',
        'status',
        'faturado',
        'tipo_calculo',
        'valor_unitario_calculo',
        'valor_frete',
        'quantidade_parcelas',
        'primeiro_vencimento',
        'forma_pagamento',
        'total',
        'observacao',
        'rua_entrega',
        'numero_entrega',
        'bairro_entrega',
        'cep_entrega',
        'referencia_entrega',
        'cidade_id_entrega',
        'usuario_id',
        'filial_id'
    ];

    /* --- Relacionamentos --- */

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function cidadeEntrega()
    {
        return $this->belongsTo(Cidade::class, 'cidade_id_entrega');
    }

    public function itens()
    {
        return $this->hasMany(ItemLocacao::class, 'locacao_id');
    }

    /* --- Accessor Inteligente para Nome da Entidade (Cliente/Fornecedor) --- */

    public function getEntidadeNomeAttribute()
    {
        if ($this->finalidade == 'coleta_fornecedor') {
            return $this->fornecedor ? "For: " . $this->fornecedor->razao_social : 'Fornecedor Não Informado';
        }

        return $this->cliente ? "Cli: " . $this->cliente->razao_social : 'Cliente Não Informado';
    }
}
