<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\NdjsonUpsertTrait;

class AnpSeeder extends Seeder
{
    use NdjsonUpsertTrait;

    public function run(): void
    {
        $this->upsertFromNdjson(
            table: 'ANP',
            uniqueBy: ['CODIGO'],
            updateColumns: ['DESCRICAO', 'ADREMICMS', 'MONOFASICO', 'PBIO', 'ORIGCOMB', 'UTRIB'],
            relativePath: 'ANP.ndjson',
            chunkSize: 500
        );
    }
}
