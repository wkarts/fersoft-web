<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CupomEcommerceUtilizado extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'cupom_id', 'cliente_id'
    ];

}
