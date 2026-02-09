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
            table: 'cnae_item_lista_servicos',
            uniqueBy: [ 'cnae', 'cod_servico'],
            updateColumns: [ 'descricao_cnae', 'descricao_servico', 'cod_trib_municipio', 'aliquota', 'permite_trib_fora', 'retencao_obrigatoria', 'permite_reducao_bc', 'deducao_max'],
            relativePath: 'CNAE_ITEM_LISTA_SERVICOS.ndjson',
            chunkSize: 500
        );
    }
}
