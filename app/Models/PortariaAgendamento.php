<?php

namespace App\Models;

class PortariaAgendamento extends BaseModel
{
    protected $table = 'portaria_agendamentos';
    protected $guarded = [];

    public function visitante() {
        return $this->belongsTo(Visitante::class, 'visitante_id');
    }

    public function funcionario() {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

    public function criador() {
        return $this->belongsTo(Usuario::class, 'usuario_criador_id');
    }
}
