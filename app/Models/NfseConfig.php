<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NfseConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome', 'razao_social', 'documento', 'regime', 'ie', 'ie', 'cnae', 'login_prefeitura',
        'senha_prefeitura', 'telefone', 'email', 'rua', 'numero', 'bairro', 'complemento', 'cep',
        'logo', 'cidade_id', 'empresa_id', 'token'
    ];

    public function cidade(){
        return $this->belongsTo(Cidade::class, 'cidade_id');
    }
}
