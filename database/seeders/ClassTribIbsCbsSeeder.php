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
            table: 'class_trib_ibs_cbs',
            uniqueBy: [ 'id_cclas_ibs_cbs'],
            updateColumns: [ 'id_cst_ibs_cbs', 'cst_ibs_cbs', 'descricao_cst_ibs_cbs', 'cclasstrib', 'nome_cclasstrib', 'descricao_cclasstrib', 'lc_214_25', 'tipo_de_aliquota', 'predibs', 'predcbs', 'pred_ibs_cbs', 'ind_redutorbc', 'ind_gtribregular', 'ind_credpres', 'indmono', 'indmonoreten', 'indmonoret', 'indmonodif', 'credito_para', 'dinivig', 'dfimvig', 'dataatualizacao'],
            relativePath: 'CLASS_TRIB_IBS_CBS.ndjson',
            chunkSize: 500
        );
    }
}
