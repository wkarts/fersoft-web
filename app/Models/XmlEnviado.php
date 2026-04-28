<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class XmlEnviado extends BaseModel
{
    use HasFactory;

    protected $fillable = [ 'empresa_id', 'documento' ];
}
