<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCompra extends BaseModel
{
    // A declaração da variável $fillable precisa ter o "=" e abrir com "["
    protected $fillable = [
        'produto_id',
        'compra_id',
        'quantidade',
        'valor_unitario',
        'unidade_compra',
        'validade',
        'cfop_entrada',
        'codigo_siad',
        
        // Impostos SPED (Registro C170)
        'cst_icms',
        'vbc_icms',
        'p_icms',
        'v_icms',
        'cst_ipi',
        'vbc_ipi',
        'p_ipi',
        'v_ipi',
        'cst_pis',
        'vbc_pis',
        'p_pis',
        'v_pis',
        'cst_cofins',
        'vbc_cofins',
        'p_cofins',
        'v_cofins',

        // Novos campos da Reforma Tributária (IBS / CBS)
        'cst_ibs_cbs',
        'bc_ibs_cbs',
        'aliq_ibs_uf',
        'aliq_cbs',
        'valor_ibs',
        'valor_cbs',
        
        // Outros campos da reforma tributária
        'cst_is',
        'valor_ibs_mono',
        'valor_cbs_mono',
        'adrem_ibs_reten',
        'adrem_cbs_reten',
        'adrem_ibs_ret',
        'adrem_cbs_ret',
        'valor_ibs_reten',
        'valor_cbs_reten',
        'valor_ibs_ret',
        'valor_cbs_ret'
    ];

    
  
    public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function compra(){
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function cidadeDesembarque(){
        return $this->belongsTo(Cidade::class, 'cidade_desembarque_id');
    }

}
