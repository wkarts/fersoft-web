<?php

namespace App\Models\ReformaTributaria;

use App\Models\Support\LookupModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassTribIbsCbs extends LookupModel
{
    protected $table = 'class_trib_ibs_cbs';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_cclas_ibs_cbs',
        'id_cst_ibs_cbs',
        'cst_ibs_cbs',
        'descricao_cst_ibs_cbs',
        'cclasstrib',
        'nome_cclasstrib',
        'descricao_cclasstrib',
        'lc_redacao',
        'lc_214_25',
        'tipo_de_aliquota',
        'predibs',
        'predcbs',
        'pred_ibs_cbs',
        'ind_redutorbc',
        'ind_gtribregular',
        'ind_credpres',
        'indmono',
        'indmonoreten',
        'indmonoret',
        'indmonodif',
        'credito_para',
        'dinivig',
        'dfimvig',
        'dataatualizacao',
        'eloquent_uuid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'id_cclas_ibs_cbs' => 'integer',
        'id_cst_ibs_cbs' => 'integer',
        'predibs' => 'decimal:4',
        'predcbs' => 'decimal:4',
        'pred_ibs_cbs' => 'decimal:4',
        'dinivig' => 'date',
        'dfimvig' => 'date',
        'dataatualizacao' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'eloquent_uuid' => 'string',
    ];

    public function cst(): BelongsTo
    {
        return $this->belongsTo(CstIbsCbs::class, 'id_cst_ibs_cbs', 'id_cst_ibs_cbs');
    }

    public function scopeByCclasstrib($q, string $cclasstrib)
    {
        return $q->where('cclasstrib', $cclasstrib);
    }
}
