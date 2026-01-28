<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategoriaContaFinanceira extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'categoria_id', 'nome'
    ];

    public function categoria(){
        return $this->belongsTo(CategoriaContaFinanceira::class, 'categoria_id');
    }
}
