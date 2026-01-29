<?php

namespace App\Models\Referencias;

use App\Models\Support\LookupModel;

class CnaeFiscal extends LookupModel
{
    protected $table = 'CNAE_FISCAL';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'CODIGO',
        'DESC_CNAE',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    public function scopeByCodigo($q, string $codigo)
    {
        return $q->where('CODIGO', $codigo);
    }
}
