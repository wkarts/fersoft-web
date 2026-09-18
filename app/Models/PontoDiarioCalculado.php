<?php

namespace App\Models;

class PontoDiarioCalculado extends BaseModel
{
    protected $table = 'ponto_diario_calculados';

    protected $fillable = [
        'empresa_id',
        'funcionario_id',
        'data',
        'entrada',
        'inicio_almoco',
        'fim_almoco',
        'saida',
        'inicio_hora_extra',
        'fim_hora_extra',
        'minutos_previstos',
        'minutos_trabalhados',
        'minutos_intervalo',
        'minutos_saldo',
        'status',
        'observacoes',
    ];

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }
}
