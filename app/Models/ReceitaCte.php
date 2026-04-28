<?php

namespace App\Models;


class ReceitaCte extends BaseModel
{	
	protected $fillable = [
		'descricao', 'cte_id', 'valor', 'data_registro'
	];

	public function cte(){
        return $this->hasOne('App\Models\Cte', 'id', 'cte_id');
    }
}
