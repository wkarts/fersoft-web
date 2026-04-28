<?php

namespace App\Models;


class CategoriaPostBlogEcommerce extends BaseModel
{
    protected $fillable = [
        'nome', 'empresa_id'
    ];

    public function posts(){
        return $this->hasMany('App\Models\PostBlogEcommerce', 'categoria_id', 'id');
    }

}
