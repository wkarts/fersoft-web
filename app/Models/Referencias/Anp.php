<?php

namespace App\Models\Referencias;

use App\Models\Support\LookupModel;

class Anp extends LookupModel
{
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';
    protected $table = 'anp';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'codigo',
        'descricao',
        'adremicms',
        'monofasico',
        'pbio',
        'origcomb',
        'utrib',
        'eloquent_uuid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'codigo' => 'integer',
        'adremicms' => 'decimal:4',
        'pbio' => 'integer',
        'origcomb' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'eloquent_uuid' => 'string',
    ];
}
