<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CupomEcommerceUtilizado extends Model
{
    use HasFactory;

    protected $fillable = [
        'cupom_id', 'cliente_id'
    ];

}
