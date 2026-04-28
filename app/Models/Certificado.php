<?php

namespace App\Models;


class Certificado extends BaseModel
{
    protected $fillable = [
		'senha', 'arquivo', 'empresa_id', 'file_name'
	];

	public function config(){
		return $this->belongsTo(ConfigNota::class, 'empresa_id');
	}
}
