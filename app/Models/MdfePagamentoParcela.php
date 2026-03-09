<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdfePagamentoParcela extends Model
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
