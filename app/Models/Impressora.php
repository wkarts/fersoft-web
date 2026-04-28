<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Impressora extends BaseModel
{
    use HasFactory;

    protected $fillable = [ 'empresa_id', 'descricao', 'porta', 'padrao', 'status' ];
    
}
