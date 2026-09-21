<?php

namespace App\Models;

class FaturaEngenharia extends BaseModel
{
    protected $table = 'faturas_engenharia';

    protected $fillable = [
        'contrato_eng_id', 'empresa_id', 'usuario_id', 'filial_id', 'cliente_id', 'vendedor_id',
        'condicao_pagamento_id', 'categoria_conta_id', 'valor_total', 'valor_retencao', 'valor_liquido',
        'data_faturamento', 'observacao', 'status', 'chave_nfse', 'protocolo_nfse', 'xml_nfse',
        'status_financeiro', 'observacoes_fatura', 'codigo_obra', 'municipio_prestacao_id',
        'numero_nfse', 'serie_nfse', 'servico_id',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
        'valor_retencao' => 'decimal:2',
        'valor_liquido' => 'decimal:2',
        'data_faturamento' => 'date',
    ];

    public function contrato()
    {
        return $this->belongsTo(ContratoEngenharia::class, 'contrato_eng_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function categoriaConta()
    {
        return $this->belongsTo(CategoriaConta::class, 'categoria_conta_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    public function cidadePrestacao()
    {
        return $this->belongsTo(Cidade::class, 'municipio_prestacao_id');
    }

    public function itens()
    {
        return $this->hasMany(FaturaEngItem::class, 'fatura_eng_id');
    }

    public function funcionarios()
    {
        return $this->hasMany(FaturaEngFuncionario::class, 'fatura_eng_id');
    }
}
