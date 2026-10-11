<?php
namespace App\Models;

class MovimentacaoDestino extends BaseModel
{
    protected $table = 'movimentacao_destinos';

    protected $guarded = [
        'id'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }
}
