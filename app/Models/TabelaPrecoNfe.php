<?php

namespace App\Models;

class TabelaPrecoNfe extends BaseModel
{
    protected $table = 'tabela_preco_nfes';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'produto_id',
        'preco_nfe',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'filial_id' => 'integer',
        'usuario_id' => 'integer',
        'produto_id' => 'integer',
        'preco_nfe' => 'decimal:2',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
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

    public static function securityResource(): array
    {
        return array_replace_recursive(parent::securityResource(), [
            'module' => 'Fiscal',
            'name' => 'Tabela de preço para NF-e',
            'plural_name' => 'Tabelas de preço para NF-e',
            'description' => 'Pauta fiscal utilizada na geração de NF-e a partir de pesagens.',
            'route_prefix' => 'tabelaPrecoNfe',
            'icon' => 'money-check-alt',
            'sensitive' => true,
            'actions' => [
                'view' => true,
                'create' => true,
                'edit' => true,
                'delete' => true,
                'restore' => false,
                'export' => false,
                'print' => false,
            ],
        ]);
    }
}
