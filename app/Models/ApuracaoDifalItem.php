<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApuracaoDifalItem extends BaseModel
{
    protected $table = 'apuracao_difal_itens';

    protected $fillable = [
        'apuracao_difal_id',
        'empresa_id',
        'filial_id',
        'nota_fiscal_id',
        'numero_nota',
        'emitente_nome',
        'valor_operacao',
        'aliquota_origem',
        'aliquota_destino',
        'valor_sem_icms_origem',
        'base_calculo_dupla',
        'icms_destino',
        'icms_origem',
        'valor_difal'
    ];
}