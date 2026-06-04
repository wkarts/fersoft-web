<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FilialInjectable;
use App\Models\MultiEmpresaTrait;

class ApuracaoDifal extends BaseModel
{
    // Seguindo o padrão do FerSoft para Multi-Empresa e Filial
    use MultiEmpresaTrait;
    use FilialInjectable;

    protected $table = 'apuracoes_difal';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'referencia',
        'data_inicial',
        'data_final',
        'valor_total_difal',
        'status',
        'contas_a_pagar_id'
    ];

    /**
     * Relacionamento com os itens da apuração (Memória de Cálculo)
     */
    public function itens()
    {
        return $this->hasMany(ApuracaoDifalItem::class, 'apuracao_difal_id');
    }

    /**
     * Relacionamento com o Contas a Pagar
     */
    public function contaPagar()
    {
        return $this->belongsTo(ContaPagar::class, 'contas_a_pagar_id');
    }
}