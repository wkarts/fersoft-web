<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoEngItem extends Model
{
    protected $table = 'contrato_eng_itens';

    protected $fillable = [
        'contrato_eng_id', 'tipo_item', 'servico_id', 'produto_id', 
        'quantidade_prevista', 'valor_unitario', 'valor_total'
    ];

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}