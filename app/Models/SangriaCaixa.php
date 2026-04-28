<?php

namespace App\Models;


class SangriaCaixa extends BaseModel
{
    protected $fillable = [
        'usuario_id', 'valor', 'empresa_id', 'observacao', 'conta_id'
    ];

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
