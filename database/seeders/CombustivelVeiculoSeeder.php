<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CombustivelVeiculo;

class CombustivelVeiculoSeeder extends Seeder
{
    public function run()
    {
        $combustiveis = [
            ['descricao' => 'Gasolina'],
            ['descricao' => 'Etanol'],
            ['descricao' => 'Diesel'],
            ['descricao' => 'GNV'],
            ['descricao' => 'Flex'],
            ['descricao' => 'Elétrico'],
            ['descricao' => 'Híbrido']
        ];

        foreach ($combustiveis as $combustivel) {
            CombustivelVeiculo::create($combustivel);
        }
    }
}
