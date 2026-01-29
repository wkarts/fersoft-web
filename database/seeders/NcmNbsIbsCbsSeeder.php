<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\NdjsonUpsertTrait;

class NcmNbsIbsCbsSeeder extends Seeder
{
    use NdjsonUpsertTrait;

    public function run(): void
    {
        $this->upsertFromNdjson(
            table: 'NCM_NBS_IBS_CBS',
            uniqueBy: ['ID_NCM_NBS_IBS_CBS'],
            updateColumns: ['ID_CCLASS_IBS_CBS', 'CST_IBS_CBS', 'CCLASS_TRIB', 'NCM_NBS_IBS_CBS', 'NOME_NCM_NBS_IBS_CBS', 'TIPO_NCM_NBS_IBS_CBS', 'INICIO_VIGENCIA', 'TERMININO_VIGENCIA'],
            relativePath: 'NCM_NBS_IBS_CBS.ndjson',
            chunkSize: 500
        );
    }
}
