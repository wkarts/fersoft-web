<?php

namespace App\Models;

class VeiculoAlertaManutencao extends BaseModel
{

    protected $table = 'veiculo_alertas_manutencao';

    protected $guarded = [
        'id'
    ];

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function manutencao()
    {
        return $this->belongsTo(Manutencao::class, 'manutencao_id', 'manutencao_id');
    }

    public function aprovador()
    {
        return $this->belongsTo(Usuario::class, 'usuario_aprovador_id');
    }

}
