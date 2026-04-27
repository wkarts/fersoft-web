<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubCategoria extends BaseModel
{
	use HasFactory;
	protected $fillable = [
		'nome', 'categoria_id'
	];

	public function categoria(){
		return $this->belongsTo(Categoria::class, 'categoria_id');
	}

}
