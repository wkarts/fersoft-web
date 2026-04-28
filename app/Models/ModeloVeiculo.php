<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModeloVeiculo extends BaseModel
{
    protected $table = 'modelo_veiculo';

    protected $fillable = [
        'descricao', 'ativo', 'empresa_id'
    ];

    public $timestamps = false;
}
