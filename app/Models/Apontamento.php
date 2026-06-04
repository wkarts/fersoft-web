<?php

namespace App\Models;


class Apontamento extends BaseModel
{
    protected $fillable = [
        'usuario_id', 'produto_id', 'quantidade', 'empresa_id', 'filial_id'
    ];

    public function produto(){
		return $this->belongsTo(Produto::class, 'produto_id');
	}

	public function usuario(){
		return $this->belongsTo(Usuario::class, 'usuario_id');
	}

}
