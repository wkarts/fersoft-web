<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubCategoriaEcommerce extends BaseModel
{
    use HasFactory;
    protected $fillable = [
		'nome', 'categoria_id'
	];

	public function categoria(){
		return $this->belongsTo(CategoriaProdutoEcommerce::class, 'categoria_id');
	}
}
