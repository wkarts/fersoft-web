<?php

namespace App\Models\ReformaTributaria;

use App\Models\Support\LookupModel;

class CstIbsCbs extends LookupModel
{
    protected $table = 'CST_IBS_CBS';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'ID_CST_IBS_CBS',
        'CST_IBS_CBS',
        'DESCRICAO_CST_IBS_CBS',
        'IND_GIBSCBS',
        'IND_GIBSCBSMONO',
        'IND_GRED',
        'IND_GDIF',
        'IND_GTRANSFCRED',
        'INDNFE',
        'INDNFCE',
        'INDCTE',
        'INDCTEOS',
        'INDBPE',
        'INDBPETM',
        'INDNF3E',
        'INDNFCOM',
        'INDNFSE',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ID_CST_IBS_CBS' => 'integer',
        'CREATED_AT' => 'datetime',
        'UPDATED_AT' => 'datetime',
        'DELETED_AT' => 'datetime',
        'ELOQUENT_UUID' => 'string',
    ];

    public function scopeByCst($q, string $cst)
    {
        return $q->where('CST_IBS_CBS', $cst);
    }
}
