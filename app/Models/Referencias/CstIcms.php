<?php

namespace App\Models\Referencias;

use App\Models\Support\LookupModel;

class CstIcms extends LookupModel
{
    protected $table = 'CST_ICMS';
    protected $primaryKey = 'CODIGO';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'CODIGO',
        'DESCRICAO',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];
}
