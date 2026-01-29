<?php

namespace App\Models\ReformaTributaria;

use App\Models\Support\LookupModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NcmNbsIbsCbs extends LookupModel
{
    protected $table = 'NCM_NBS_IBS_CBS';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'ID_NCM_NBS_IBS_CBS',
        'ID_CCLASS_IBS_CBS',
        'CST_IBS_CBS',
        'CCLASS_TRIB',
        'NCM_NBS_IBS_CBS',
        'NOME_NCM_NBS_IBS_CBS',
        'TIPO_NCM_NBS_IBS_CBS',
        'INICIO_VIGENCIA',
        'TERMININO_VIGENCIA',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ID_NCM_NBS_IBS_CBS' => 'integer',
        'ID_CCLASS_IBS_CBS' => 'integer',
        'INICIO_VIGENCIA' => 'date',
        'TERMININO_VIGENCIA' => 'date',
        'CREATED_AT' => 'datetime',
        'UPDATED_AT' => 'datetime',
        'DELETED_AT' => 'datetime',
        'ELOQUENT_UUID' => 'string',
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
