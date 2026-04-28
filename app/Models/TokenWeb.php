<?php

namespace App\Models;


class TokenWeb extends BaseModel
{
     protected $fillable = [
		'token', 'cliente_id'
	];
}
