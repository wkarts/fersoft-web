<?php

namespace App\Models;

use App\Models\BaseModel; // Herdando toda a inteligência do seu BaseModel
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConciliacaoRegra extends BaseModel
{
    use HasFactory;

    protected $table = 'conciliacao_regras';
}