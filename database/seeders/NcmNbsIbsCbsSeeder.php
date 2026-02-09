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
            table: 'ncm_nbs_ibs_cbs',
            uniqueBy: [ 'id_ncm_nbs_ibs_cbs'],
            updateColumns: [ 'id_cclass_ibs_cbs', 'cst_ibs_cbs', 'cclass_trib', 'ncm_nbs_ibs_cbs', 'nome_ncm_nbs_ibs_cbs', 'tipo_ncm_nbs_ibs_cbs', 'inicio_vigencia', 'terminino_vigencia'],
            relativePath: 'NCM_NBS_IBS_CBS.ndjson',
            chunkSize: 500
        );
    }
}
