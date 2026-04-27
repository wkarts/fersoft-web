<?php

namespace App\Models;


class EnderecoEcommerce extends BaseModel
{
    protected $fillable = [
		'rua', 'numero', 'bairro', 'cep', 'cidade', 'uf', 'complemento', 'cliente_id'
	];

	public function cliente(){
        return $this->belongsTo(ClienteEcommerce::class, 'cliente_id');
    }

	public static function estados(){
		return [
			"AC",
			"AL",
			"AM",
			"AP",
			"BA",
			"CE",
			"DF",
			"ES",
			"GO",
			"MA",
			"MG",
			"MS",
			"MT",
			"PA",
			"PB",
			"PE",
			"PI",
			"PR",
			"RJ",
			"RN",
			"RS",
			"RO",
			"RR",
			"SC",
			"SE",
			"SP",
			"TO"	
		];
	}
}
