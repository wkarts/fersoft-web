<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FilialInjectable;

class PedidoIfood extends BaseModel
{
    use HasFactory, FilialInjectable;

    protected $fillable = [
        'status',
        'pedido_id',
        'data_pedido',
        'empresa_id',
        'usuario_id',
        'filial_id',
        // 'tipo_pedido', 'endereco', 'bairro', 'cep', 'nome_cliente', 'id_cliente',
        // 'telefone_cliente', 'valor_produtos', 'valor_entrega', 'valor_total',
        // 'taxas_adicionais'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial(){
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function itens(){
        return $this->hasMany(ItemPedidoIfood::class, 'pedido_id');
    }

    public function payments(){
        return $this->hasMany(PagamentoPedidoIfood::class, 'pedido_id');
    }

    public function venda(){
        return $this->hasOne(VendaCaixa::class, 'pedido_ifood_id');
    }
}
