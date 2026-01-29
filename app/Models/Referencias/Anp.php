<?php

namespace App\Models\Referencias;

use App\Models\Support\LookupModel;

class Anp extends LookupModel
{
    protected $table = 'ANP';
    protected $primaryKey = 'CODIGO';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'CODIGO',
        'DESCRICAO',
        'ADREMICMS',
        'MONOFASICO',
        'PBIO',
        'ORIGCOMB',
        'UTRIB',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    protected $casts = [
        'CODIGO' => 'integer',
        'ADREMICMS' => 'decimal:4',
        'PBIO' => 'integer',
        'ORIGCOMB' => 'integer',
        'CREATED_AT' => 'datetime',
        'UPDATED_AT' => 'datetime',
        'DELETED_AT' => 'datetime',
        'ELOQUENT_UUID' => 'string',
    ];
}
