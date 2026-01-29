<?php

namespace App\Models\ReformaTributaria;

use App\Models\Support\LookupModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassTribIbsCbs extends LookupModel
{
    protected $table = 'CLASS_TRIB_IBS_CBS';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'ID_CCLAS_IBS_CBS',
        'ID_CST_IBS_CBS',
        'CST_IBS_CBS',
        'DESCRICAO_CST_IBS_CBS',
        'CCLASSTRIB',
        'NOME_CCLASSTRIB',
        'DESCRICAO_CCLASSTRIB',
        'LC_REDACAO',
        'LC_214_25',
        'TIPO_DE_ALIQUOTA',
        'PREDIBS',
        'PREDCBS',
        'PRED_IBS_CBS',
        'IND_REDUTORBC',
        'IND_GTRIBREGULAR',
        'IND_CREDPRES',
        'INDMONO',
        'INDMONORETEN',
        'INDMONORET',
        'INDMONODIF',
        'CREDITO_PARA',
        'DINIVIG',
        'DFIMVIG',
        'DATAATUALIZACAO',
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ID_CCLAS_IBS_CBS' => 'integer',
        'ID_CST_IBS_CBS' => 'integer',
        'PREDIBS' => 'decimal:4',
        'PREDCBS' => 'decimal:4',
        'PRED_IBS_CBS' => 'decimal:4',
        'DINIVIG' => 'date',
        'DFIMVIG' => 'date',
        'DATAATUALIZACAO' => 'datetime',
        'CREATED_AT' => 'datetime',
        'UPDATED_AT' => 'datetime',
        'DELETED_AT' => 'datetime',
        'ELOQUENT_UUID' => 'string',
    ];

    public function cst(): BelongsTo
    {
        return $this->belongsTo(CstIbsCbs::class, 'ID_CST_IBS_CBS', 'ID_CST_IBS_CBS');
    }

    public function scopeByCclasstrib($q, string $cclasstrib)
    {
        return $q->where('CCLASSTRIB', $cclasstrib);
    }
}
