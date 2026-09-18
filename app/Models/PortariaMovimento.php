<?php

namespace App\Models;

class PortariaMovimento extends BaseModel
{
    protected $table = 'portaria_movimentos';
    protected $guarded = ['id'];

    // Garante que o Laravel converta automaticamente para instâncias do Carbon
    protected $casts = [
        'data_hora_entrada' => 'datetime',
        'data_hora_saida'   => 'datetime',
    ];

    /**
     * O visitante que entrou
     */
    public function visitante()
    {
        return $this->belongsTo(Visitante::class, 'visitante_id');
    }

    /**
     * O funcionário que está recebendo a visita
     */
    public function funcionarioVisitado()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_visitado_id');
    }

    /**
     * O usuário (porteiro) que autorizou a entrada no sistema
     */
    public function porteiro()
    {
        return $this->belongsTo(Usuario::class, 'usuario_porteiro_id');
    }

    /**
     * O agendamento que originou este movimento (se não for visita surpresa)
     */
    public function agendamento()
    {
        return $this->belongsTo(PortariaAgendamento::class, 'agendamento_id');
    }

    /**
     * Verifica se o visitante ainda está na empresa
     */
    public function isPresente()
    {
        return is_null($this->data_hora_saida);
    }
}
