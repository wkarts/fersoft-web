<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AvisoAcesso extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'aviso_id',
        'empresa_id',
        'usuario_id',
        'created_at',
        'updated_at'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

}
