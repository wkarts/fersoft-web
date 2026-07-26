<?php
namespace App\Models;


class ManifestaNfseTomada extends BaseModel
{
    protected $table = 'manifesta_nfse_tomadas';
    protected $fillable = [
        'empresa_id', 'filial_id', 'chave', 'nsu', 'numero_nota', 'data_emissao', 
        'prestador_nome', 'prestador_cnpj_cpf', 'valor_servico', 'valor_liquido', 
        'compra_servico_id', 'fatura_salva', 'situacao'
    ];
}