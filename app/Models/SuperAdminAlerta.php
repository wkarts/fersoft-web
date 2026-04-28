<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SuperAdminAlerta extends BaseModel
{
    use HasFactory;

     protected $fillable = [
        'empresa_id', 'tipo', 'mensagem', 'visto'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
