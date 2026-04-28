<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComissaoAssessor extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'venda_caixa_id', 'valor', 'status', 'assessor_id'
    ];

    public function venda(){
        return $this->belongsTo(VendaCaixa::class, 'venda_caixa_id');
    }

    public function assessor(){
        return $this->belongsTo(Acessor::class, 'assessor_id');
    }
}
