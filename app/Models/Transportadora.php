<?php

namespace App\Models;


class Transportadora extends BaseModel
{
    protected $fillable = [
		'razao_social', 'cnpj_cpf', 'logradouro', 'cidade_id', 'empresa_id', 'email',
        'telefone', 'numero'
	];

	public function cidade(){
		return $this->belongsTo(Cidade::class, 'cidade_id');
	}

	public static function verificaCadastrado($cnpj){
    	$value = session('user_logged');
        $empresa_id = $value['empresa'];
        $transp = Transportadora::where('cnpj_cpf', $cnpj)
        ->where('empresa_id', $empresa_id)
        ->first();
        return $transp;
    }
}
