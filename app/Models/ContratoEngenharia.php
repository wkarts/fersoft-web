<?php

namespace App\Models;

class ContratoEngenharia extends BaseModel
{
    protected $table = 'contratos_engenharia';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'cliente_id', 'vendedor_id', 'centro_custo_id',
        'numero_contrato', 'contato_nome', 'contato_telefone', 'cep_obra', 'endereco_obra', 
        'numero_obra', 'bairro_obra', 'cidade_obra_id', 'valor_contrato', 'valor_faturado', 
        'percentual_retencao', 'data_inicio', 'data_fim', 'arquivo_contrato', 'status', 'observacoes'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function filial()
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Funcionario::class, 'vendedor_id');
    }

    public function itens()
    {
        return $this->hasMany(ContratoEngItem::class, 'contrato_eng_id');
    }

    public function faturas()
    {
        return $this->hasMany(FaturaEngenharia::class, 'contrato_eng_id');
    }
}