<?php

namespace App\Models;

class ClienteVeiculo extends BaseModel
{
    protected $table = 'cliente_veiculos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'placa',
        'marca',
        'modelo',
        'ano',
        'cor',
        'km_atual',
        'combustivel',
        'chassi'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function ordensServico()
    {
        return $this->hasMany(OrdemServico::class, 'cliente_veiculo_id')->orderBy('id', 'desc');
    }

    // Acessor para exibição nos selects
    public function getDescricaoCompletaAttribute()
    {
        return strtoupper("{$this->placa} - {$this->marca} {$this->modelo} ({$this->cor})");
    }
}
