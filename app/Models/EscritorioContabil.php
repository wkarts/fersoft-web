<?php

namespace App\Models;


class EscritorioContabil extends BaseModel
{
    protected $fillable = [
        'razao_social', 'nome_fantasia', 'cnpj', 'ie', 'logradouro',
        'numero', 'bairro', 'fone', 'email', 'cep', 'empresa_id', 'token_sieg', 
        'envio_automatico_xml_contador', 'cidade_id', 'crc', 'cpf'
    ];

    public function cidade(){
        return $this->belongsTo(Cidade::class, 'cidade_id');
    }
}
