<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaConta extends Model
{
    protected $table = 'categoria_contas';

    protected $fillable = [
        'nome',
        'empresa_id',
        'filial_id',
        'usuario_id',
        'tipo',
        'dre_grupo',
        'incluir_resultado'
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'filial_id' => 'integer',
        'usuario_id' => 'integer',
        'incluir_resultado' => 'boolean',
    ];

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
