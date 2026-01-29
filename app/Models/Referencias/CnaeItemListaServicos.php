<?php

namespace App\Models\Referencias;

use App\Models\Support\LookupModel;

class CnaeItemListaServicos extends LookupModel
{
    protected $table = 'CNAE_ITEM_LISTA_SERVICOS';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'CNAE',
        'DESCRICAO_CNAE',
        'COD_SERVICO',
        'DESCRICAO_SERVICO',
        'COD_TRIB_MUNICIPIO',
        'ALIQUOTA',
        'PERMITE_TRIB_FORA',
        'RETENCAO_OBRIGATORIA',
        'PERMITE_REDUCAO_BC',
        'DEDUCAO_MAX',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    protected $casts = [
        'ID' => 'integer',
        'CNAE' => 'integer',
        'COD_SERVICO' => 'integer',
        'COD_TRIB_MUNICIPIO' => 'integer',
        'ALIQUOTA' => 'decimal:2',
        'DEDUCAO_MAX' => 'decimal:2',
        'CREATED_AT' => 'datetime',
        'UPDATED_AT' => 'datetime',
        'DELETED_AT' => 'datetime',
        'ELOQUENT_UUID' => 'string',
    ];

    public function scopeByKey($q, int $cnae, int $codServico)
    {
        return $q->where('CNAE', $cnae)->where('COD_SERVICO', $codServico);
    }
}
