<?php

namespace App\Models\Referencias;

use App\Models\Support\LookupModel;

class CstIpi extends LookupModel
{
    protected $table = 'CST_IPI';
    protected $primaryKey = 'CODIGO';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'CODIGO',
        'DESCRICAO',
        'TIPO',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];
}
