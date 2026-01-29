<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Traits\NdjsonUpsertTrait;

class CnaeItemListaServicosSeeder extends Seeder
{
    use NdjsonUpsertTrait;

    public function run(): void
    {
        $this->upsertFromNdjson(
            table: 'CNAE_ITEM_LISTA_SERVICOS',
            uniqueBy: ['CNAE', 'COD_SERVICO'],
            updateColumns: ['DESCRICAO_CNAE', 'DESCRICAO_SERVICO', 'COD_TRIB_MUNICIPIO', 'ALIQUOTA', 'PERMITE_TRIB_FORA', 'RETENCAO_OBRIGATORIA', 'PERMITE_REDUCAO_BC', 'DEDUCAO_MAX'],
            relativePath: 'CNAE_ITEM_LISTA_SERVICOS.ndjson',
            chunkSize: 500
        );
    }
}
