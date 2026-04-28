<?php

namespace App\Models;


class UsuarioAcesso extends BaseModel
{
    protected $fillable = [
        'usuario_id', 'status', 'hash', 'ip_address'
    ];

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
