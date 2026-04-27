<?php

namespace App\Models;


class MdfePagamento extends BaseModel
{
    protected $table = 'mdfe_pagamentos';

    protected $fillable = [
        'mdfe_id',
        'tipo_doc_pagador',
        'cpf_cnpj_pagador',
        'nome_pagador',
        'forma_pagamento',
        'valor_pagamento'
    ];

    public function mdfe()
    {
        return $this->belongsTo(Mdfe::class, 'mdfe_id');
    }

    public function componentes()
    {
        return $this->hasMany(MdfePagamentoComponente::class, 'mdfe_pagamento_id');
    }

    public function parcelas()
    {
        return $this->hasMany(MdfePagamentoParcela::class, 'mdfe_pagamento_id');
    }
}
