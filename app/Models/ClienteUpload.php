<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'file_name', 'descricao', 'estensao'
    ];
}
