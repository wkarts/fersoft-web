<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVendaBalcao extends Model
{

    protected $fillable = [
        'produto_id', 'venda_balcao_id', 'quantidade', 'valor', 'sub_total'
    ];

    public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function venda(){
        return $this->belongsTo(VendaBalcao::class, 'venda_balcao_id');
    }
}
