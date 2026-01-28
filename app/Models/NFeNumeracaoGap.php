<?php

namespace App\Models;

class NFeNumeracaoGap extends BaseModel
{
    protected $table = 'vw_nfe_numeracao_gaps';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'data_doc_anterior' => 'datetime',
        'data_doc_atual' => 'datetime',
    ];
}
