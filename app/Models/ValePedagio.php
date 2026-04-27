<?php

namespace App\Models;


class ValePedagio extends BaseModel
{
    protected $fillable = [
		'mdfe_id', 'cnpj_fornecedor', 'cnpj_fornecedor_pagador', 'numero_compra', 'valor'
	];
}
