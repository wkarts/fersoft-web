<?php

namespace App\Models;


class MdfePagamentoComponente extends BaseModel
{
    protected $table = 'mdfe_pagamento_componentes';

    protected $fillable = [
        'mdfe_pagamento_id',
        'tipo_componente',
        'descricao',
        'valor'
    ];

    public function pagamento()
    {
        return $this->belongsTo(MdfePagamento::class, 'mdfe_pagamento_id');
    }
}
