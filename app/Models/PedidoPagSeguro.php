<?php

namespace App\Models;

use App\Traits\FilialInjectable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoPagSeguro extends BaseModel
{
    use HasFactory, FilialInjectable;

    protected $fillable = [
		'pedido_delivery_id',
        'empresa_id',
        'usuario_id',
        'filial_id',
        'numero_cartao',
        'cpf',
        'nome_impresso',
        'codigo_transacao',
        'referencia',
        'parcelas',
		'bandeira',
        'status'
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

}
