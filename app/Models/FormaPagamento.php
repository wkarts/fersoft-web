<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormaPagamento extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'empresa_id', 'nome', 'chave', 'taxa', 'status', 'prazo_dias', 'tipo_taxa', 'infos'
    ];

}
