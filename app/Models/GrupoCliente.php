<?php

namespace App\Models;


class GrupoCliente extends BaseModel
{
    protected $fillable = [
        'nome', 'empresa_id'
    ];

    public function clientes(){
		return $this->hasMany('App\Models\Cliente', 'grupo_id', 'id');
	}
	
}
