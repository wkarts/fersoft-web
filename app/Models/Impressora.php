<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Impressora extends Model
{
    use HasFactory;

    protected $fillable = [ 'empresa_id', 'descricao', 'porta', 'padrao', 'status' ];
    
}
