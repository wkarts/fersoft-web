<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarcaVeiculo extends BaseModel
{
    protected $table = 'marca_veiculo';

    protected $fillable = [
        'descricao', 'ativo', 'empresa_id'
    ];

    public $timestamps = false;
}
