<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AvisoAcesso extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'aviso_id'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
    
}
