<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailConfig extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'host', 'email', 'senha', 'porta', 'cripitografia', 'smtp_auth', 'smtp_debug',
        'nome'
    ];
}
