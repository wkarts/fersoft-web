<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\NdjsonUpsertTrait;

class CstIbsCbsSeeder extends Seeder
{
    use NdjsonUpsertTrait;

    public function run(): void
    {
        $this->upsertFromNdjson(
            table: 'cst_ibs_cbs',
            uniqueBy: [ 'id_cst_ibs_cbs'],
            updateColumns: [ 'cst_ibs_cbs', 'descricao_cst_ibs_cbs', 'ind_gibscbs', 'ind_gibscbsmono', 'ind_gred', 'ind_gdif', 'ind_gtransfcred', 'indnfe', 'indnfce', 'indcte', 'indcteos', 'indbpe', 'indbpetm', 'indnf3e', 'indnfcom', 'indnfse'],
            relativePath: 'CST_IBS_CBS.ndjson',
            chunkSize: 500
        );
    }
}
