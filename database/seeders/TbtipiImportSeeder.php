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
            table: 'tbtipi_import',
            uniqueBy: [ 'ncm', 'ex', 'lc214_codigo_raw', 'cclasstrib'],
            updateColumns: [ 'descricao', 'aliquota_raw', 'aliquota_perc', 'cst_ibs_cbs', 'tipo_reducao'],
            relativePath: 'TBTIPI_IMPORT.ndjson',
            chunkSize: 500
        );
    }
}
