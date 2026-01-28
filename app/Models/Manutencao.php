<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manutencao extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'manutencoes';

    protected $primaryKey = 'manutencao_id';

    public $incrementing = true;

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'veiculo_id',
        'responsavel_id',
        'fornecedor_id',
        'descricao',
        'tipo',
        'data_manutencao',
        'custo',
        'prioridade',
        'checklist',
        'status',
        'quilometragem_atual',
        'proxima_manutencao_km',
        'proxima_manutencao_data',
        'observacoes',
    ];

    protected $casts = [
        'checklist' => 'array',
        'data_manutencao' => 'date',
        'proxima_manutencao_data' => 'date',
        'quilometragem_atual' => 'float',
        'proxima_manutencao_km' => 'float',
        'custo' => 'float',
    ];

    protected $appends = ['id'];

    public function getIdAttribute(): ?int
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    protected static function booted(): void
    {
        static::saved(function (Manutencao $manutencao) {
            $manutencao->sincronizarResumoVeiculo();
        });
    }

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function responsavel()
    {
        return $this->belongsTo(Funcionario::class, 'responsavel_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function atualizarChecklist(array $itens): void
    {
        $this->checklist = $itens;
    }

    public function sincronizarResumoVeiculo(): void
    {
        $veiculo = $this->veiculo;

        if (!$veiculo) {
            return;
        }

        $dadosAtualizacao = [];
        $quilometragemVeiculo = (float) ($veiculo->quilometragem ?? 0);
        $quilometragemAtual = $this->quilometragem_atual ?? null;

        if ($quilometragemAtual !== null && $quilometragemAtual > $quilometragemVeiculo) {
            $dadosAtualizacao['quilometragem'] = $quilometragemAtual;
        }

        if ($this->status === 'Concluída') {
            $dadosAtualizacao['status_manutencao'] = 'Em dia';
            $dadosAtualizacao['data_ultima_manutencao'] = $this->data_manutencao;
            if ($quilometragemAtual !== null) {
                $dadosAtualizacao['quilometragem_ultima_manutencao'] = $quilometragemAtual;
            }
        } elseif ($this->status === 'Em andamento') {
            $dadosAtualizacao['status_manutencao'] = 'Em manutenção';
        } else {
            $dadosAtualizacao['status_manutencao'] = 'Atenção';
        }

        if ($this->proxima_manutencao_data) {
            $dadosAtualizacao['data_proxima_revisao'] = $this->proxima_manutencao_data;
        }

        if ($this->proxima_manutencao_km !== null) {
            $dadosAtualizacao['proxima_manutencao_km'] = $this->proxima_manutencao_km;
        }

        if (!empty($this->observacoes)) {
            $dadosAtualizacao['observacoes_manutencao'] = $this->observacoes;
        }

        if (!empty($dadosAtualizacao)) {
            $veiculo->fill($dadosAtualizacao);
            $veiculo->save();
        }
    }
}
