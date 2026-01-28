<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarcaVeiculo extends Model
{
    protected $table = 'marca_veiculo';

    protected $fillable = [
        'descricao', 'ativo', 'empresa_id'
    ];

    public $timestamps = false;
}
