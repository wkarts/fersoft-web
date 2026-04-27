<?php

namespace App\Models;


class TokenClienteDelivery extends BaseModel
{
    protected $fillable = [
		'token', 'cliente_id', 'user_id'
	];
}
