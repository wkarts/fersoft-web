<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancelamentoLicenca extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'justificativa', 'leitura_super'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
