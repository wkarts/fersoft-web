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
            table: 'nbs',
            uniqueBy: [ 'codigo'],
            updateColumns: [ 'desc_nbs', 'aliq_nac', 'aliq_imp'],
            relativePath: 'NBS.ndjson',
            chunkSize: 500
        );
    }
}
