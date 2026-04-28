<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BairroDeliveryLoja extends BaseModel
{
    use HasFactory;
    protected $fillable = [ 'empresa_id', 'nome', 'valor_entrega' ];

}
