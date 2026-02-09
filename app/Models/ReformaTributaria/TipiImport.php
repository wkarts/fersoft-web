<?php

namespace App\Models\ReformaTributaria;

use App\Models\Support\LookupModel;

class TipiImport extends LookupModel
{
    protected $table = 'tbtipi_import';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'ncm',
        'ex',
        'descricao',
        'aliquota_raw',
        'aliquota_perc',
        'cst_ibs_cbs',
        'cclasstrib',
        'tipo_reducao',
        'lc214_codigo_raw',
        'eloquent_uuid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'aliquota_perc' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'eloquent_uuid' => 'string',
    ];

    public function scopeKey($q, string $ncm, ?string $ex, string $lc214, string $cclasstrib)
    {
        return $q->where('ncm', $ncm)
            ->where('ex', $ex)
            ->where('lc214_codigo_raw', $lc214)
            ->where('cclasstrib', $cclasstrib);
    }
}
