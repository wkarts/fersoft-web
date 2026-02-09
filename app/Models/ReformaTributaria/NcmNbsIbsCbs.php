<?php

namespace App\Models\ReformaTributaria;

use App\Models\Support\LookupModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NcmNbsIbsCbs extends LookupModel
{
    protected $table = 'ncm_nbs_ibs_cbs';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_ncm_nbs_ibs_cbs',
        'id_cclass_ibs_cbs',
        'cst_ibs_cbs',
        'cclass_trib',
        'ncm_nbs_ibs_cbs',
        'nome_ncm_nbs_ibs_cbs',
        'tipo_ncm_nbs_ibs_cbs',
        'inicio_vigencia',
        'terminino_vigencia',
        'eloquent_uuid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'id_ncm_nbs_ibs_cbs' => 'integer',
        'id_cclass_ibs_cbs' => 'integer',
        'inicio_vigencia' => 'date',
        'terminino_vigencia' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'eloquent_uuid' => 'string',
    ];

    // Relacionamento “conservador” usando o legado
    public function classTrib(): BelongsTo
    {
        return $this->belongsTo(ClassTribIbsCbs::class, 'ID_CCLASS_IBS_CBS', 'ID_CCLAS_IBS_CBS');
    }

    // Scopes úteis
    public function scopeByNcm($q, string $ncm)
    {
        return $q->where('NCM_NBS_IBS_CBS', $ncm);
    }

    public function scopeVigenteEm($q, ?string $data = null)
    {
        $data = $data ?: date('Y-m-d');

        return $q->where(function ($qq) use ($data) {
            $qq->whereNull('INICIO_VIGENCIA')->orWhere('INICIO_VIGENCIA', '<=', $data);
        })->where(function ($qq) use ($data) {
            $qq->whereNull('TERMININO_VIGENCIA')->orWhere('TERMININO_VIGENCIA', '>=', $data);
        });
    }
}
