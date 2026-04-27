<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryConfigGaleria extends BaseModel
{
    use HasFactory;

    protected $fillable = [ 'config_id', 'imagem' ];
}
