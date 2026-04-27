<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApkComanda extends BaseModel
{
    use HasFactory;
    protected $fillable = [ 'nome_arquivo', 'empresa_id' ];
}
