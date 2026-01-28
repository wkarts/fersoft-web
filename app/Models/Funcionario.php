<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    protected $fillable = [
        'nome',
        'bairro',
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
        'data_nascimento',
        'data_admissao',
        'tipo_sanguineo',
        'status_funcionario',
        'numero_registro',
        'observacoes',
        'foto_funcionario'
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
}
