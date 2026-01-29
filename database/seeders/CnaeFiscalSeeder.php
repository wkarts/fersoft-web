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
            table: 'CNAE_FISCAL',
            uniqueBy: ['CODIGO'],
            updateColumns: ['DESC_CNAE'],
            relativePath: 'CNAE_FISCAL.ndjson',
            chunkSize: 500
        );
    }
}
