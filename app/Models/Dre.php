<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DreCategoria;
//use App\Models\BaseModel;

class Dre extends Model
{
    protected $fillable = [
        'empresa_id',
        'inicio',
        'fim',
        'observacao',
        'percentual_imposto',
        'lucro_prejuizo',
        'filial_id',
    ];

    /**
     * Garante que 'inicio' e 'fim' sejam Carbon,
     * e que percentuais e lucros sejam sempre float.
     */
    protected $casts = [
        'inicio'             => 'date',
        'fim'                => 'date',
        'percentual_imposto' => 'float',
        'lucro_prejuizo'     => 'float',
    ];

    /**
     * Cria as categorias padrão no momento em que o DRE é instanciado.
     */
    public function criaCategoriasPreDefinidas()
    {
        foreach ($this->categoriaNomes() as $nome) {
            DreCategoria::create([
                'nome'   => $nome,
                'dre_id' => $this->id,
            ]);
        }
    }

    /**
     * Lista de nomes de categorias, na ordem esperada pelas regras do DRE.
     * Corrigido typo em 'Faturamento Bruto'.
     */
    private function categoriaNomes(): array
    {
        return [
            'Faturamento Bruto',
            'Total de Deduções',
            'Faturamento Líquido',
            'Custos de Produção Variáveis',
            'Custos Fixos e Despesas',
        ];
    }

    /**
     * Relacionamento com categorias, já ordenado por 'id' para manter
     * sempre o mesmo índice (0, 1, 2, …) ao percorrer.
     */
    public function categorias()
    {
        return $this->hasMany(DreCategoria::class, 'dre_id', 'id')
            ->orderBy('id');
    }
}
