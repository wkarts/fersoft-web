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
        // Novos campos adicionados:
        'conta_contabil_despesa_id',
        'conta_contabil_provisao_id',
        'ignora_terceiro',
        'gera_provisao'
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'filial_id' => 'integer',
        'usuario_id' => 'integer',
        'incluir_resultado' => 'boolean',
        // Casting dos novos campos para facilitar o uso no Laravel:
        'conta_contabil_despesa_id' => 'integer',
        'conta_contabil_provisao_id' => 'integer',
        'ignora_terceiro' => 'boolean',
        'gera_provisao' => 'boolean',
    ];

    // Relacionamentos com o plano de contas (ajuda a buscar o nome da conta na view)
    public function contaDespesa()
    {
        return $this->belongsTo(PlanoContasContabil::class, 'conta_contabil_despesa_id');
    }

    public function contaProvisao()
    {
        return $this->belongsTo(PlanoContasContabil::class, 'conta_contabil_provisao_id');
    }

    public static function gruposDRE()
    {
        return [
            'receita_bruta'   => 'Receitas (Vendas)',
            'devolucao'       => 'Devoluções de Vendas',
            'cmv'             => 'Custo de Mercadoria (CMV)',
            'administrativa'  => 'Despesas Administrativas',
            'operacional'     => 'Despesas Operacionais',
            'tributaria'      => 'Despesas Tributárias (Impostos)',
            'financeira'      => 'Receitas/Despesas Financeiras',
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
