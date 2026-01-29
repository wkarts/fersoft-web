<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\NdjsonUpsertTrait;

class ClassTribIbsCbsSeeder extends Seeder
{
    use NdjsonUpsertTrait;

    public function run(): void
    {
        $this->upsertFromNdjson(
            table: 'CLASS_TRIB_IBS_CBS',
            uniqueBy: ['ID_CCLAS_IBS_CBS'],
            updateColumns: ['ID_CST_IBS_CBS', 'CST_IBS_CBS', 'DESCRICAO_CST_IBS_CBS', 'CCLASSTRIB', 'NOME_CCLASSTRIB', 'DESCRICAO_CCLASSTRIB', 'LC_214_25', 'TIPO_DE_ALIQUOTA', 'PREDIBS', 'PREDCBS', 'PRED_IBS_CBS', 'IND_REDUTORBC', 'IND_GTRIBREGULAR', 'IND_CREDPRES', 'INDMONO', 'INDMONORETEN', 'INDMONORET', 'INDMONODIF', 'CREDITO_PARA', 'DINIVIG', 'DFIMVIG', 'DATAATUALIZACAO'],
            relativePath: 'CLASS_TRIB_IBS_CBS.ndjson',
            chunkSize: 500
        );
    }
}
