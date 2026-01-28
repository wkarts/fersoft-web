<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeloVeiculo extends Model
{
    protected $table = 'modelo_veiculo';

    protected $fillable = [
        'descricao', 'ativo', 'empresa_id'
    ];

    public $timestamps = false;
}
