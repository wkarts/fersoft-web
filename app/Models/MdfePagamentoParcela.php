<?php

namespace App\Models;


class MdfePagamentoParcela extends BaseModel
{
    protected $table = 'mdfe_pagamento_parcelas';

    protected $fillable = [
        'mdfe_pagamento_id',
        'numero_parcela',
        'data_vencimento',
        'valor'
    ];

    public function pagamento()
    {
        return $this->belongsTo(MdfePagamento::class, 'mdfe_pagamento_id');
    }
}
