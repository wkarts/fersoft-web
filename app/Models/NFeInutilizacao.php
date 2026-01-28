<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NFeInutilizacao extends BaseModel
{
    protected $table = 'nfe_inutilizacoes';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'modelo',
        'serie',
        'numero_inicial',
        'numero_final',
        'ano',
        'justificativa',
        'ambiente',
        'origem',
        'status',
        'protocolo',
        'mensagem',
        'nfserver_id',
        'xml_solicitacao',
        'xml_retorno',
    ];

    public function usuario()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'usuario_id');
    }

}
