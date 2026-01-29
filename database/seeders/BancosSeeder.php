<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\NdjsonUpsertTrait;

class BancosSeeder extends Seeder
{
    use NdjsonUpsertTrait;

    public function run(): void
    {
        $this->upsertFromNdjson(
            table: 'BANCOS',
            uniqueBy: ['CODIGO'],
            updateColumns: ['DESCRICAO'],
            relativePath: 'BANCOS.ndjson',
            chunkSize: 500
        );
    }
}
