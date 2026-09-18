<?php

namespace App\Models;

class Visitante extends BaseModel
{
    protected $table = 'visitantes';

    // Protege apenas o ID, permitindo mass assignment nos demais campos
    protected $guarded = ['id'];

    /**
     * Se o visitante representar um Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Se o visitante representar um Fornecedor
     */
    public function fornecedor()
    {
        // Certifique-se de que o model Fornecedor tenha esse nome no seu projeto
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }
}
