<?php

namespace App\Models;

class EquipamentoCaixa extends BaseModel
{
    protected $table = 'equipamentos_caixas';
    protected $guarded = ['id'];

    public function clienteAtual()
    {
        return $this->belongsTo(Cliente::class, 'cliente_atual_id');
    }
}
