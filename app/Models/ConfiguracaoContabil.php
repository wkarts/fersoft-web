<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoContabil extends Model
{
    protected $table = 'configuracoes_contabeis'; // Importante: nome da tabela no BD
    protected $guarded = ['id']; // Permite salvar todos os campos que criamos
}