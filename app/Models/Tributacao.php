<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tributacao extends Model
{
	protected $fillable = [
		'icms',
        'pis',
        'cofins',
        'regime',
        'ipi',
        'ncm_padrao',
        'empresa_id',
        'link_nfse',
		'perc_ap_cred',
        'exclusao_icms_pis_cofins',
        'aliq_cbs',
        'aliq_ibs_uf',
        'aliq_ibs_mun',
        'cst_ibs_cbs',
        'class_trib_ibs_cbs',
        'perc_red_ibs',
        'perc_red_cbs',
    ];

	public static function regimes(){
		return [
			0 => 'Simples',
			1 => 'Normal',
			2 => 'MEI',
		];
	}
}
