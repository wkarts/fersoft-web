<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SolicitacaoColeta extends BaseModel
{
    use HasFactory;

    protected $table = 'solicitacoes_coleta';

    protected $fillable = [
        'empresa_id',
        'pessoa_id',
        'material',
        'dias_disponiveis',
        'fotos',
        'observacao',
        'status',
        'data_agendada'
    ];

    // Cria o relacionamento: Uma solicitação pertence a uma Pessoa
    public function pessoa()
    {
        return $this->belongsTo(PessoaPreCadastro::class, 'pessoa_id');
    }

}
