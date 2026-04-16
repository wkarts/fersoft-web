<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManutencaoItem extends Model
{
    protected $table = 'manutencao_itens';

    protected $fillable = [
        'manutencao_id',
        'produto_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'subtotal',
		'usuario_id',
		'filial_id',
		'empresa_id'
    ];

    public function manutencao()
    {
        return $this->belongsTo(Manutencao::class, 'manutencao_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}