<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProducaoCorte extends Model
{
    protected $table = 'producao_cortes';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'producao_lote_id', 'codigo_sublote',
        'produto_gerado_id', 'balanca_config_id', 'peso_bruto', 'peso_bag', 'peso_liquido',
        'peso_origem', 'quantidade_vendida', 'saldo_disponivel', 'status_estoque',
        'equipe_ids', 'equipe_nomes', 'balanca_evidence_json', 'camera_snapshots_json',
        'imagens_persistidas_json', 'observacoes'
    ];

    protected $casts = [
        'equipe_ids' => 'array',
    ];

    public function lote()
    {
        return $this->belongsTo(ProducaoLote::class, 'producao_lote_id');
    }

    public function produtoGerado()
    {
        return $this->belongsTo(Produto::class, 'produto_gerado_id');
    }

    public function destinacoes()
    {
        return $this->hasMany(ProducaoDestinacao::class, 'producao_corte_id');
    }

    public function produto()
    {

        return $this->belongsTo(\App\Models\Produto::class, 'produto_gerado_id');
    }
}
