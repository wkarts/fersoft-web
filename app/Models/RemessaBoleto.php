<?php

namespace App\Models;


class RemessaBoleto extends BaseModel
{
    protected $fillable = [
        'remessa_id', 'boleto_id'
    ];

    public function remessa(){
		return $this->belongsTo(Remessa::class, 'remessa_id');
	}

	public function boleto(){
		return $this->belongsTo(Boleto::class, 'boleto_id');
	}

}
