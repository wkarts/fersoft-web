<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubCategoriaContaFinanceira extends BaseModel
{
    use HasFactory;

    protected $fillable = [ 
        'categoria_id', 'nome'
    ];

    public function categoria(){
        return $this->belongsTo(CategoriaContaFinanceira::class, 'categoria_id');
    }
}
