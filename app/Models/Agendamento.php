<?php

namespace App\Models;


class Agendamento extends BaseModel
{

    protected $fillable = [
        'funcionario_id', 'cliente_id', 'data', 'inicio', 'termino', 'observacao', 'total',
        'desconto', 'acrescimo', 'status', 'empresa_id'
    ];

    public function itens(){
        return $this->hasMany('App\Models\ItemAgendamento', 'agendamento_id', 'id');
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function funcionario(){
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

    public function veiculo()
    {
        return $this->belongsTo(\App\Models\ClienteVeiculo::class, 'cliente_veiculo_id');
    }

}
