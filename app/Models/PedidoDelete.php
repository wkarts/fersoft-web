<?php

namespace App\Models;

use App\Traits\FilialInjectable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoDelete extends BaseModel
{
    use HasFactory, FilialInjectable;

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'endereco_id',
		'pedido_id',
        'produto',
        'quantidade',
        'valor',
        'data_insercao'
	];

    public function empresa(){
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
