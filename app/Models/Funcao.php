<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funcao extends Model
{
    // O nome da tabela que criamos no SQL
    protected $table = 'funcoes';

    // Campos que permitimos salvar (importante para o seu Modal de salvamento rápido)
    protected $fillable = [
        'nome', 
        'empresa_id'
    ];

    // Se você não criou as colunas 'created_at' e 'updated_at' na tabela 'funcoes',
    // deixe a linha abaixo como false para não dar erro ao salvar:
    public $timestamps = false; 
}