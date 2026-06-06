<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tarefa extends BaseModel
{
    use HasFactory;

    protected $table = 'tarefas';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'funcionario_id',
        'user_id',
        'titulo',
        'descricao',
        'data',
        'hora_estimada',
        'status',
        'iniciado_em',
        'finalizado_em',
        'tempo_gasto_minutos',
        'is_recorrente',
        'frequencia',
        'data_limite',
      	'hora_limite',
    	'prioridade',
    ];

    /**
     * Mapeamento de tipos de dados (Casts)
     * Isso converte automaticamente os campos do banco para objetos Carbon no Laravel,
     * facilitando muito a manipulação de datas e horas.
     */
    protected $casts = [
        'data' => 'date',
        'data_limite' => 'date',
        'iniciado_em' => 'datetime',
        'finalizado_em' => 'datetime',
        'is_recorrente' => 'boolean',
        'tempo_gasto_minutos' => 'integer'
    ];

    /**
     * Relacionamento: A tarefa pertence a uma Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relacionamento: A tarefa pode pertencer a uma Filial (se não for a matriz)
     */
    public function filial()
    {
        return $this->belongsTo(Filial::class); // Se tiver o model Filial
    }

    /**
     * Relacionamento: A tarefa é designada para um Funcionário
     */
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }

    /**
     * Relacionamento: O Usuário logado que criou ou é dono do registro da tarefa
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Escopo local para filtrar facilmente apenas tarefas pendentes
     * Uso no controller: Tarefa::pendentes()->get();
     */
    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    /**
     * Escopo local para filtrar apenas tarefas em andamento
     */
    public function scopeEmAndamento($query)
    {
        return $query->where('status', 'em_andamento');
    }
}