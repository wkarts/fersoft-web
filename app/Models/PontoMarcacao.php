<?php

namespace App\Models;

class PontoMarcacao extends BaseModel
{
    //protected $guarded = [];

    protected $table = 'ponto_marcacoes';

    protected $fillable = [
        'empresa_id',
        'funcionario_id',
        'ponto_afd_registro_id',
        'data_hora_marcacao',
        'origem',
        'tipo_marcacao',
        'status',
        'dados_brutos',
        'inconsistente',
        'latitude',
        'longitude',
        'endereco_traccar',
        'movimentacao_veiculo_id',
        'observacoes'
    ];

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

}
