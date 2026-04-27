<?php

namespace App\Models;


class ItemIBTE extends BaseModel
{	
	protected $table = 'item_i_b_t_es';
	
    protected $fillable = [
		'ibte_id', 'codigo', 'descricao', 'nacional_federal', 'importado_federal', 'estadual',
		'municipal'
	];
}
