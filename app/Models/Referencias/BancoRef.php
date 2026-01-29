<?php

namespace App\Models\Referencias;

use App\Models\Support\LookupModel;

class BancoRef extends LookupModel
{
    protected $table = 'BANCOS';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'CODIGO',
        'DESCRICAO',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    protected $casts = [
        'ID' => 'integer',
        'CODIGO' => 'integer',
        'CREATED_AT' => 'datetime',
        'UPDATED_AT' => 'datetime',
        'DELETED_AT' => 'datetime',
        'ELOQUENT_UUID' => 'string',
    ];

    public function scopeByCodigo($q, int $codigo)
    {
        return $q->where('CODIGO', $codigo);
    }
}
