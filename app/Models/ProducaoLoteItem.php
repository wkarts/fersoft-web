<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProducaoLoteItem extends Model
{
    use HasFactory;

    protected $table = 'producao_lote_itens';

    protected $fillable = [
        'empresa_id',
        'producao_lote_id',
        'produto_id',
        'funcionario_id',
        'peso',
        'peso_bag',
        'peso_liquido',
        'balanca_config_id',
    ];

    public function lote()
    {
        return $this->belongsTo(ProducaoLote::class, 'producao_lote_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'usuario_id');
    }
}
