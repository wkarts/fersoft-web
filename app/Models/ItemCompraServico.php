<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ItemCompraServico extends Model
{
    protected $table = 'item_compra_servicos';
    protected $fillable = [
        'compra_id', 'codigo_servico_lc116', 'discriminacao', 'valor_servico',
        'vbc_iss', 'p_iss', 'v_iss', 'iss_retido', 'v_irrf', 'v_inss', 'v_csll', 'v_pis', 'v_cofins'
    ];
}