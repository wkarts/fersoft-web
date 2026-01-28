<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoMovimentacao extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tipos_movimentacoes';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'nome',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoVeiculo::class, 'tipo_movimentacao_id');
    }

}
