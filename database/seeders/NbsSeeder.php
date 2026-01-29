<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\NdjsonUpsertTrait;

class NbsSeeder extends Seeder
{
    use NdjsonUpsertTrait;

    public function run(): void
    {
        $this->upsertFromNdjson(
            table: 'NBS',
            uniqueBy: ['CODIGO'],
            updateColumns: ['DESC_NBS', 'ALIQ_NAC', 'ALIQ_IMP'],
            relativePath: 'NBS.ndjson',
            chunkSize: 500
        );
    }
}
