<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MultiEmpresaTrait;

class ContaEmpresa extends BaseModel
{
    use HasFactory;
    use MultiEmpresaTrait;

    protected $table = 'conta_empresas';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'nome',
        'banco',
        'agencia',
        'conta',
        'plano_conta_id',
        'saldo_inicial',
        'status',
        'saldo'
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'filial_id' => 'integer',
        'usuario_id' => 'integer',
        'plano_conta_id' => 'integer',
        'saldo' => 'float',
        'saldo_inicial' => 'float',
        'status' => 'boolean',
    ];

    public function plano()
    {
        return $this->belongsTo(PlanoConta::class, 'plano_conta_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial()
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function scopeDaEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeDaFilial($query, $filialId)
    {
        if ($filialId === 'matriz') {
            return $query->where(function ($q) {
                $q->whereNull('filial_id')
                    ->orWhere('filial_id', 0);
            });
        }

        if ($filialId !== null && $filialId !== '' && $filialId !== 'todos') {
            return $query->where('filial_id', $filialId);
        }

        return $query;
    }
}
