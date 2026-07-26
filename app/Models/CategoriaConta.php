<?php

namespace App\Models;

class CategoriaConta extends BaseModel
{
    protected $table = 'categoria_contas';

    protected $fillable = [
        'nome',
        'empresa_id',
        'filial_id',
        'usuario_id',
        'tipo',
        'dre_grupo',
        'incluir_resultado',
        'conta_contabil_despesa_id',
        'conta_contabil_provisao_id',
        'ignora_terceiro',
        'gera_provisao',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'filial_id' => 'integer',
        'usuario_id' => 'integer',
        'incluir_resultado' => 'boolean',
        'conta_contabil_despesa_id' => 'integer',
        'conta_contabil_provisao_id' => 'integer',
        'ignora_terceiro' => 'boolean',
        'gera_provisao' => 'boolean',
    ];

    public function contaDespesa()
    {
        return $this->belongsTo(PlanoContasContabil::class, 'conta_contabil_despesa_id');
    }

    public function contaProvisao()
    {
        return $this->belongsTo(PlanoContasContabil::class, 'conta_contabil_provisao_id');
    }

    public static function gruposDRE(): array
    {
        return [
            'receita_bruta' => 'Receitas (Vendas)',
            'deducao_venda' => 'Deduções da Venda (Impostos sobre faturamento)',
            'devolucao' => 'Devoluções de Vendas',
            'cmv' => 'Custo de Mercadoria (CMV)',
            'pessoal' => 'Despesas com Pessoal (Folha)',
            'administrativa' => 'Despesas Administrativas',
            'operacional' => 'Despesas Operacionais',
            'tributaria' => 'Despesas Tributárias (Outros Impostos e Taxas)',
            'financeira' => 'Receitas/Despesas Financeiras',
            'nao_operacional' => 'Não Operacionais',
        ];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial()
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
