<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProducaoLote extends Model
{
    protected $table = 'producao_lotes';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'codigo_lote',
        'pesagem_entrada_id', 'fornecedor_id', 'cliente_origem_id',
        'cnpj_origem', 'cnpj_raiz', 'nfe_entrada', 'mtr_entrada', 'foto_chegada',
        'produto_origem_id', 'peso_entrada', 'peso_produzido', 'peso_perda',
        'nfe_perda_id', 'nfe_perda_numero', 'nfe_perda_chave', 'nfe_perda_status',
        'status', 'observacoes', 'concluido_em'
    ];

    // Extrai e normaliza CNPJ completo (14) e Raiz (8) automaticamente
    public static function extrairCnpjERaiz(?string $documento): array
    {
        $limpo = preg_replace('/\D/', '', (string) $documento);
        $raiz  = strlen($limpo) === 14 ? substr($limpo, 0, 8) : $limpo;
        return [
            'cnpj_origem' => $limpo ?: null,
            'cnpj_raiz'   => $raiz ?: null,
        ];
    }

    public function getSaldoRestanteAttribute(): float
    {
        return max(0, (float)$this->peso_entrada - (float)$this->peso_produzido - (float)$this->peso_perda);
    }

    public function cortes()
    {
        return $this->hasMany(ProducaoCorte::class, 'producao_lote_id');
    }

    public function destinacoes()
    {
        return $this->hasMany(ProducaoDestinacao::class, 'producao_lote_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function clienteOrigem()
    {
        return $this->belongsTo(Cliente::class, 'cliente_origem_id');
    }

    public function produtoOrigem()
    {
        return $this->belongsTo(Produto::class, 'produto_origem_id');
    }


    public function itens()
    {
        return $this->hasMany(ProducaoLoteItem::class, 'producao_lote_id');
    }


    public function ticketsSaida()
    {
        return $this->hasMany(\App\Models\TicketPesagem::class, 'producao_lote_id')
            ->whereHas('pesagem', function($q) {
                $q->where('tipo', 'venda');
            })
            ->with(['pesagem.cliente', 'pesagem.venda']);
    }
}
