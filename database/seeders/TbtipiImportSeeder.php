<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\NdjsonUpsertTrait;

class TbtipiImportSeeder extends Seeder
{
    use NdjsonUpsertTrait;

    public function run(): void
    {
        $this->upsertFromNdjson(
            table: 'TBTIPI_IMPORT',
            uniqueBy: ['NCM', 'EX', 'LC214_CODIGO_RAW', 'CCLASSTRIB'],
            updateColumns: ['DESCRICAO', 'ALIQUOTA_RAW', 'ALIQUOTA_PERC', 'CST_IBS_CBS', 'TIPO_REDUCAO'],
            relativePath: 'TBTIPI_IMPORT.ndjson',
            chunkSize: 500
        );
    }
}
