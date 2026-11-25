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
        'observacao',
        'fotos',
        'tipo_transporte',
        'status',
        'data_reserva',
        'turno',
        'tipo_veiculo',
        'produto_residuo_id',
        'produto_cacamba_id',
        'qtd_cacambas',
        'peso_estimado',
        'unidade_peso',
        'tempo_franquia_horas',
        'valor_taxa_extra',
        'aceitou_termo_taxa'
    ];

    // Cria o relacionamento: Uma solicitação pertence a uma Pessoa
    public function pessoa()
    {
        return $this->belongsTo(PessoaPreCadastro::class, 'pessoa_id');
    }

}
