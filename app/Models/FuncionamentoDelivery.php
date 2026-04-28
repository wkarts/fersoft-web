<?php

namespace App\Models;


class FuncionamentoDelivery extends BaseModel
{

    protected $fillable = [
        'ativo', 'dia', 'inicio_expediente', 'fim_expediente', 'empresa_id'
    ];

    public static function dias(){
    	return [
            'DOMINGO',
    		'SEGUNDA',	
    		'TERÇA',	
    		'QUARTA',	
    		'QUINTA',	
    		'SEXTA',	
    		'SABADO'
    	];
    }

    public static function getDia($dia){
        return FuncionamentoDelivery::dias()[$dia];
    }
}
