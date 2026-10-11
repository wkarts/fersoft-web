<?php

namespace App\Models;

class VeiculoPlanoPreventivo extends BaseModel
{

    protected $table = 'veiculo_planos_preventivos';

    protected $guarded = [
        'id'
    ];

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

}
