<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimentacaoVeiculo extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'movimentacoes_veiculos';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'veiculo_id',
        'motorista_id',
        'tipo_movimentacao_id',
        'data_movimentacao',
        'km_saida',
        'km_chegada',
        'custo',
        'observacoes',
        'status',
    ];

    protected $casts = [
        'data_movimentacao' => 'date',
        'km_saida' => 'float',
        'km_chegada' => 'float',
        'custo' => 'float',
    ];

    protected static function booted(): void
    {
        static::saved(function (MovimentacaoVeiculo $movimentacao) {
            $veiculo = $movimentacao->veiculo;

            if (!$veiculo) {
                return;
            }

            $kmChegada = $movimentacao->km_chegada;
            $kmSaida = $movimentacao->km_saida;
            $novoKm = $kmChegada ?? $kmSaida;

            if ($novoKm === null) {
                return;
            }

            $quilometragemAtual = (float) ($veiculo->quilometragem ?? 0);

            if ($novoKm > $quilometragemAtual) {
                $veiculo->quilometragem = $novoKm;
                $veiculo->save();
            }
        });
    }

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function motorista()
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function tipoMovimentacao()
    {
        return $this->belongsTo(TipoMovimentacao::class);
    }
}

