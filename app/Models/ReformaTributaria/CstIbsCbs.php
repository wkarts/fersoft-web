<?php

namespace App\Models\ReformaTributaria;

use App\Models\Support\LookupModel;

class CstIbsCbs extends LookupModel
{
    protected $table = 'cst_ibs_cbs';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_cst_ibs_cbs',
        'cst_ibs_cbs',
        'descricao_cst_ibs_cbs',
        'ind_gibscbs',
        'ind_gibscbsmono',
        'ind_gred',
        'ind_gdif',
        'ind_gtransfcred',
        'indnfe',
        'indnfce',
        'indcte',
        'indcteos',
        'indbpe',
        'indbpetm',
        'indnf3e',
        'indnfcom',
        'indnfse',
        'eloquent_uuid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    protected $casts = [
        'id' => 'integer',
        'id_cst_ibs_cbs' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'eloquent_uuid' => 'string',
    ];

    public function scopeByCst($q, string $cst)
    {
        return $q->where('cst_ibs_cbs', $cst);
    }
}
