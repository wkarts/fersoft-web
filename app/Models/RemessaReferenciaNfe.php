<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemessaReferenciaNfe extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'remessa_id', 'chave'
    ];
}
