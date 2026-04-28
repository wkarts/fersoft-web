<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Difal extends BaseModel
{
    protected $fillable = [
        'empresa_id', 'uf', 'pICMSUFDest', 'pICMSInter', 'pICMSInterPart', 'cfop', 'pFCPUFDest'
    ];

}
