<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProducaoDestinacao extends Model
{
    protected $table = 'producao_destinacoes';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'producao_lote_id', 'producao_corte_id',
        'venda_id', 'pesagem_saida_id', 'movimentacao_veiculo_id', 'cliente_destino_id',
        'cnpj_destino', 'quantidade_utilizada', 'nfe_saida_numero', 'nfe_saida_chave', 'mtr_saida_numero'
    ];

    public function lote()
    {
        return $this->belongsTo(ProducaoLote::class, 'producao_lote_id');
    }

    public function corte()
    {
        return $this->belongsTo(ProducaoCorte::class, 'producao_corte_id');
    }

    public function clienteDestino()
    {
        return $this->belongsTo(Cliente::class, 'cliente_destino_id');
    }
}
