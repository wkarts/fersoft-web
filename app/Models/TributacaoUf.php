<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TributacaoUf extends Model
{
    use HasFactory;
    protected $fillable = [
        'produto_id', 'uf', 'percentual_icms', 'percentual_red_bc', 'percentual_fcp', 'percentual_icms_interno'
    ];

    public function produto(){
		return $this->belongsTo(Produto::class, 'produto_id');
	}
}
