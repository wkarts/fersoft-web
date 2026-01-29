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
            table: 'CST_IBS_CBS',
            uniqueBy: ['ID_CST_IBS_CBS'],
            updateColumns: ['CST_IBS_CBS', 'DESCRICAO_CST_IBS_CBS', 'IND_GIBSCBS', 'IND_GIBSCBSMONO', 'IND_GRED', 'IND_GDIF', 'IND_GTRANSFCRED', 'INDNFE', 'INDNFCE', 'INDCTE', 'INDCTEOS', 'INDBPE', 'INDBPETM', 'INDNF3E', 'INDNFCOM', 'INDNFSE'],
            relativePath: 'CST_IBS_CBS.ndjson',
            chunkSize: 500
        );
    }
}
