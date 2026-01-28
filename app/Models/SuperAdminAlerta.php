<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperAdminAlerta extends Model
{
    use HasFactory;

     protected $fillable = [
        'empresa_id', 'tipo', 'mensagem', 'visto'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
