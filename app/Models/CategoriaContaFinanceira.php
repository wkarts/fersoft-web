<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaContaFinanceira extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'empresa_id', 'nome', 'tipo'
    ];

    public function subcategorias(){
        return $this->hasMany(SubCategoriaContaFinanceira::class, 'categoria_id');
    }
}
