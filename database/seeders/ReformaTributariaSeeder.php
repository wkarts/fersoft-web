<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReformaTributariaSeeder extends Seeder
{
    public function run(): void
    {
        // Ordem sugerida (dependências lógicas):
        $this->call([
            CstIbsCbsSeeder::class,
            ClassTribIbsCbsSeeder::class,
            NcmNbsIbsCbsSeeder::class,

            CnaeFiscalSeeder::class,
            CnaeItemListaServicosSeeder::class,

            NbsSeeder::class,
            BancosSeeder::class,
            AnpSeeder::class,

            TbtipiImportSeeder::class,
        ]);
    }
}
