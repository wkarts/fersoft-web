<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CupomDescontoEcommerce extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'valor', 'status', 'codigo', 'tipo', 'descricao', 'valor_minimo_pedido'
    ];
}
