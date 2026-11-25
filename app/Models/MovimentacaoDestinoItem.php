<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimentacaoDestinoItem extends Model
{
    protected $table = 'movimentacao_destino_itens';

    protected $fillable = [
        'movimentacao_veiculo_id',
        'movimentacao_destino_id',
        'produto_id',
        'quantidade',
        'unidade',
        'observacao'
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function destino()
    {
        return $this->belongsTo(MovimentacaoDestino::class, 'movimentacao_destino_id');
    }
}
