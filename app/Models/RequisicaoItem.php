<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisicaoItem extends Model
{
    protected $table = 'requisicao_itens';

    protected $fillable = [
        'requisicao_id', 
        'produto_id', 
        'quantidade'
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}