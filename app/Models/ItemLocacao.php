<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemLocacao extends BaseModel
{
    use HasFactory;
    protected $fillable = [
		'locacao_id', 'produto_id', 'observacao', 'valor'
	];

	public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
