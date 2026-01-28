<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemTransferencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'transferencia_id',
        'produto_id',
        'quantidade',
        'valor_unitario',
        'sub_total'
    ];

    public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }

}
