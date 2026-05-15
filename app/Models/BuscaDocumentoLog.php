<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BuscaDocumentoLog extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'resultado', 'sucesso', 'filial_id'
    ];
}
