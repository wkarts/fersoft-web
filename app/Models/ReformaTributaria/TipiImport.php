<?php

namespace App\Models\ReformaTributaria;

use App\Models\Support\LookupModel;

class TipiImport extends LookupModel
{
    protected $table = 'TBTIPI_IMPORT';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'NCM',
        'EX',
        'DESCRICAO',
        'ALIQUOTA_RAW',
        'ALIQUOTA_PERC',
        'CST_IBS_CBS',
        'CCLASSTRIB',
        'TIPO_REDUCAO',
        'LC214_CODIGO_RAW',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ALIQUOTA_PERC' => 'decimal:4',
        'CREATED_AT' => 'datetime',
        'UPDATED_AT' => 'datetime',
        'DELETED_AT' => 'datetime',
        'ELOQUENT_UUID' => 'string',
    ];

    public function scopeKey($q, string $ncm, ?string $ex, string $lc214, string $cclasstrib)
    {
        return $q->where('NCM', $ncm)
            ->where('EX', $ex)
            ->where('LC214_CODIGO_RAW', $lc214)
            ->where('CCLASSTRIB', $cclasstrib);
    }
}
