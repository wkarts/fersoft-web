<?php

namespace App\Models;


class ContatoEcommerce extends BaseModel
{
    protected $fillable = [
		'nome', 'email', 'texto', 'empresa_id'
	];
}
