<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\NdjsonUpsertTrait;

class CnaeFiscalSeeder extends Seeder
{
    use NdjsonUpsertTrait;

    public function run(): void
    {
        $this->upsertFromNdjson(
            table: 'cnae_fiscal',
            uniqueBy: [ 'codigo'],
            updateColumns: [ 'desc_cnae'],
            relativePath: 'CNAE_FISCAL.ndjson',
            chunkSize: 500
        );
    }
}
