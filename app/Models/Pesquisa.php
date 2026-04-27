<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pesquisa extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'titulo', 'texto', 'status', 'maximo_acessos'
    ]; 

    public function respostas(){
        return $this->hasMany(PesquisaResposta::class, 'pesquisa_id');
    }
}
