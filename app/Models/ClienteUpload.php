<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClienteUpload extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'file_name', 'descricao', 'estensao'
    ];
}
