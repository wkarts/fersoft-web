<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CancelamentoLicenca extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'justificativa', 'leitura_super'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
