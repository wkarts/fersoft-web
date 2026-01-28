<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteOtica extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'esf_od_longe', 'cil_od_longe', 'eixo_od_longe', 'dnp_od_longe', 'dp_od_longe',
        'esf_oe_longe', 'cli_oe_longe', 'eixo_oe_longe', 'dnp_oe_longe', 'esf_od_perto', 
        'cil_od_perto', 'eixo_od_perto', 'adicao_od_perto', 'altura_od_perto', 'dnp_od_perto', 
        'dp_od_perto', 'esf_oe_perto', 'cil_oe_perto', 'eixo_oe_perto', 'adicao_oe_perto', 
        'altura_oe_perto', 'dnp_oe_perto', 'armacao', 'qtd_armacao', 'valor_armacao', 
        'lente', 'qtd_lente', 'valor_lente', 'tratamento', 'medico', 'tipo_lente', 
        'previsao_retorno_dias', 'data', 'referencia', 'observacao'
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
