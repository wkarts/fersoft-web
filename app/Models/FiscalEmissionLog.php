<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FiscalEmissionLog extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'document_type',
        'document_id',
        'document_reference',
        'numero',
        'serie',
        'chave',
        'ambiente',
        'status',
        'retorno_codigo',
        'retorno_mensagem',
        'recibo',
        'xml_envio_path',
        'xml_retorno_path',
        'retorno_payload',
    ];
}
