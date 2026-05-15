<?php

namespace App\Models;

class SpedRegra1400 extends BaseModel
{
    protected $table = 'sped_regras_1400';
    
    protected $fillable = [
        'empresa_id', 
        'filial_id', 
        'cfop', 
        'codigo_ipm', 
        'descricao'
    ];
}