<?php

namespace App\Models;

class FaturaEngItem extends BaseModel
{
    protected $table = 'fatura_eng_itens';

    protected $fillable = [
        'fatura_engenharia_id',
      	'fatura_eng_id',
        'tipo_item',
        'servico_id',
        'produto_id',
        'quantidade',
        'valor_unitario',
        'valor_total'
    ];

    public function fatura()
    {
        return $this->belongsTo(FaturaEngenharia::class, 'fatura_engenharia_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}