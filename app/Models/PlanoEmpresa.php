<?php

namespace App\Models;


class PlanoEmpresa extends BaseModel
{
    protected $fillable = [
        'empresa_id', 'plano_id', 'expiracao', 'mensagem_alerta', 'valor'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function plano(){
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function payment(){
        return $this->hasOne('App\Models\Payment', 'plano_id', 'id');
    }

    public function getValor(){
        if($this->valor == 0) return $this->plano->valor;
        return $this->valor;
    }
}
