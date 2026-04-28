<?php

namespace App\Models;


class ItemAgendamento extends BaseModel
{
    protected $fillable = [
        'agendamento_id', 'servico_id', 'quantidade'
    ];

    public function servico(){
        return $this->belongsTo(Servico::class, 'servico_id');
    }

}
