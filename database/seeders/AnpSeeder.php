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
            table: 'anp',
            uniqueBy: [ 'codigo'],
            updateColumns: [ 'descricao', 'adremicms', 'monofasico', 'pbio', 'origcomb', 'utrib'],
            relativePath: 'ANP.ndjson',
            chunkSize: 500
        );
    }
}
