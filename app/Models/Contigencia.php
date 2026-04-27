<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contigencia extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'status', 'tipo', 'motivo', 'status_retorno', 'documento'
    ];

    public static function tiposContigencia(){
        return [
            'SVCRS',
            'SVCAN',
            'OFFLINE',
        ];
    }
}
