<?php

namespace App\Models\Referencias;

use App\Models\Support\LookupModel;

class Nbs extends LookupModel
{
    protected $table = 'NBS';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'CODIGO',
        'DESC_NBS',
        'ALIQ_NAC',
        'ALIQ_IMP',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ALIQ_NAC' => 'decimal:2',
        'ALIQ_IMP' => 'decimal:2',
        'CREATED_AT' => 'datetime',
        'UPDATED_AT' => 'datetime',
        'DELETED_AT' => 'datetime',
        'ELOQUENT_UUID' => 'string',
    ];

    public function scopeByCodigo($q, string $codigo)
    {
        return $q->where('CODIGO', $codigo);
    }
}
