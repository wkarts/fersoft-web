<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PessoaPreCadastro extends BaseModel
{
    use HasFactory;

    // Define o nome exato da tabela no banco de dados
    protected $table = 'pessoas_pre_cadastro';

    // Libera os campos para inserção em massa (Mass Assignment)
    protected $fillable = [
        'empresa_id',

        // Dados Principais
        'nome_completo',
        'tipo_pessoa',
        'cpf_cnpj',
        'regime_tributario',
        'simples_nacional',
        'rg_ie',
        'exibe_contratos',

        // Contato
        'telefone',
        'email',

        // Endereço de Coleta
        'rua_coleta',
        'numero_coleta',
        'bairro_coleta',
        'cep_coleta',
        'cidade_coleta',
        'estado_coleta',
        'endereco_cobranca',

        // Referências
        'pessoa_contato',
        'responsavel_comercial',
        'origem_cadastro',

        // Dados Bancários
        'banco',
        'agencia',
        'conta',
        'digito',
        'tipo_conta',
        'chave_pix',

        // Controle
        'status',

        // 🚀 ADICIONADOS PARA RASTREABILIDADE
        'exige_rastreabilidade',
        'portal_nivel_visao',
    ];

    // Converte o campo boolean automaticamente
    protected $casts = [
        'simples_nacional' => 'boolean',
    ];
}
