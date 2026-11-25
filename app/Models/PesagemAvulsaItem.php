<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesagemAvulsaItem extends Model
{
    protected $table = 'pesagens_avulsas_itens';

    protected $fillable = [
        'pesagem_avulsa_id',
        'produto_id',
        'produto_nome',
        'peso_entrada',
        'peso_saida',
        'peso_liquido',
        'status',
    ];

    public function pesagem()
    {
        return $this->belongsTo(PesagemAvulsa::class, 'pesagem_avulsa_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
