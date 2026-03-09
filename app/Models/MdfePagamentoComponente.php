<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdfePagamentoComponente extends Model
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
