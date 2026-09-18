<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funcionario extends BaseModel
{
    protected $fillable = [
        'nome',
        'bairro',
        'cep',
        'numero',
        'rua',
        'cpf',
        'rg',
        'telefone',
        'celular',
        'email',
        'data_registro',
        'empresa_id',
        'usuario_id',
        'percentual_comissao',
        'salario',
        'cnh',
        'categoria_cnh',
        'vencimento_cnh',
        'status_motorista',
        'tipo_ponto',
        'data_nascimento',
        'data_admissao',
        'tipo_sanguineo',
        'status_funcionario',
        'numero_registro',
        'matricula',
        'pis',
        'data_demissao',
        'jornada_padrao_id',
        'escala_padrao_id',
        'gestor_id',
        'centro_custo_id',
        'ativo_ponto_mobile',
        'codigo_relogio',
        'observacao_ponto',
        'observacoes',
        'foto_funcionario',
        'funcao_id',
        'traccar_id',
        'filial_id',
        'latitude_residencia',
        'longitude_residencia',
        'pin_ponto',
        'whatsapp_id',
        'recebe_alerta_coleta'
    ];

    protected $appends = ['imgApp', 'img'];

    /**
     * Acessor para a imagem com caminho completo
     */
    public function getImgAppAttribute()
    {
        if (empty($this->foto_funcionario)) {
            return env("PATH_URL") . "/imgs/no_funcionarios.png";
        }
        return env("PATH_URL") . "/imgs_funcionarios/{$this->foto_funcionario}";
    }

    /**
     * Acessor para a imagem padrão
     */
    public function getImgAttribute()
    {
        if ($this->foto_funcionario == "") {
            return "/imagens/sem-imagem.png";
        }
        return env("PATH_URL") . "/imgs_funcionarios/" . $this->foto_funcionario;
    }

    public function getImgFuncionarioAttribute()
    {
        if (empty($this->foto_funcionario)) {
            return env("PATH_URL") . "/imgs/no_image.png";
        }
        return env("PATH_URL") . "/imgs_funcionarios/{$this->foto_funcionario}";
    }

    /**
     * Relacionamento com Contatos
     */
    public function contatos()
    {
        return $this->hasMany('App\Models\ContatoFuncionario', 'funcionario_id', 'id');
    }

    /**
     * Relacionamento com Usuário
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relacionamento com Eventos do Funcionário
     */
    public function eventos()
    {
        return $this->hasMany(FuncionarioEvento::class, 'funcionario_id');
    }

    /**
     * Relacionamento com Eventos Ativos
     */
    public function eventosAtivos()
    {
        return $this->hasMany(FuncionarioEvento::class, 'funcionario_id')->where('ativo', 1);
    }

    /**
     * Relacionamento com Veículos para Motoristas
     */
    public function veiculos()
    {
        return $this->hasMany(Veiculo::class, 'motorista_id', 'id');
    }

    public function funcao()
    {
        return $this->belongsTo(\App\Models\Funcao::class, 'funcao_id');
    }
}
