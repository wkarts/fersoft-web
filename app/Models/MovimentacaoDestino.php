<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimentacaoDestino extends Model
{
    protected $table = 'movimentacao_destinos';
    protected $guarded = ['id'];

    public function movimentacao()
    {
        return $this->belongsTo(MovimentacaoVeiculo::class, 'movimentacao_veiculo_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function caixaDeixada()
    {
        return $this->belongsTo(Produto::class, 'caixa_deixada_id');
    }

    public function caixaRetirada()
    {
        return $this->belongsTo(Produto::class, 'caixa_retirada_id');
    }

    public function produtoResiduo()
    {
        return $this->belongsTo(Produto::class, 'produto_residuo_id');
    }

    // Itens pesados e coletados nesta parada (multi-resíduos com balança)
    public function itens()
    {
        return $this->hasMany(MovimentacaoDestinoItem::class, 'movimentacao_destino_id');
    }

    // Vínculo com a caçamba física alocada/deixada no patrimônio
    public function patrimonioDeixado()
    {
        return $this->belongsTo(\App\Models\PatrimonioEquipamento::class, 'cacamba_colocada_id');
    }

    // Vínculo com a caçamba física recolhida no patrimônio
    public function patrimonioRetirado()
    {
        return $this->belongsTo(\App\Models\PatrimonioEquipamento::class, 'cacamba_retirada_id');
    }
}
