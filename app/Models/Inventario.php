<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventario extends BaseModel
{
    use HasFactory;
    protected $fillable = [
		'inicio', 'fim', 'observacao', 'tipo', 'status', 'empresa_id', 'referencia'
	];

	public static function tipos(){
		return [
			'Anual',
			'Periódico',
			'Permanente',
			'Rotativo',
			'Geral'
		];
	}

	public function itens(){
        return $this->hasMany('App\Models\ItemInventario', 'inventario_id', 'id');
    }
}
