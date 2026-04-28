<?php

namespace App\Models;


class AlteracaoEstoque extends BaseModel
{
    protected $fillable = [
		'produto_id', 'usuario_id', 'quantidade', 'tipo', 'observacao', 'empresa_id', 'motivo'
	];

	public function produto(){
		return $this->belongsTo(Produto::class, 'produto_id');
	}

	public function usuario(){
		return $this->belongsTo(Usuario::class, 'usuario_id');
	}
}
