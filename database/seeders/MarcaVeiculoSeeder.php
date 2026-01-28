<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MarcaVeiculo;

class MarcaVeiculoSeeder extends Seeder
{
    public function run()
    {
        $marcas = [
            // Marcas de Automóveis
            ['descricao' => 'Fiat'],
            ['descricao' => 'Volkswagen'],
            ['descricao' => 'Ford'],
            ['descricao' => 'Chevrolet'],
            ['descricao' => 'Toyota'],
            ['descricao' => 'Honda'],
            ['descricao' => 'Hyundai'],
            ['descricao' => 'Renault'],
            ['descricao' => 'Peugeot'],
            ['descricao' => 'Nissan'],
            ['descricao' => 'BMW'],
            ['descricao' => 'Mercedes-Benz (Automóveis)'],
            ['descricao' => 'Audi'],
            ['descricao' => 'Kia'],
            ['descricao' => 'Jeep'],
            ['descricao' => 'Citroën'],
            ['descricao' => 'Land Rover'],
            ['descricao' => 'Mitsubishi'],
            ['descricao' => 'Subaru'],
            ['descricao' => 'Volvo (Automóveis)'],
            ['descricao' => 'Mini'],
            ['descricao' => 'Jaguar'],
            ['descricao' => 'Porsche'],
            ['descricao' => 'Lexus'],
            ['descricao' => 'Chery'],

            // Marcas de Caminhões
            ['descricao' => 'Scania'],
            ['descricao' => 'Volvo (Caminhões)'],
            ['descricao' => 'Mercedes-Benz (Caminhões)'],
            ['descricao' => 'MAN'],
            ['descricao' => 'Iveco'],
            ['descricao' => 'Ford Caminhões'],
            ['descricao' => 'DAF'],
            ['descricao' => 'Volkswagen (Caminhões)'],
            ['descricao' => 'Mack Trucks'],
            ['descricao' => 'Kenworth'],
            ['descricao' => 'International'],
            ['descricao' => 'Peterbilt'],
            ['descricao' => 'Tatra'],
            ['descricao' => 'Foton'],
            ['descricao' => 'Hyundai Caminhões'],
            ['descricao' => 'Renault Trucks'],
            ['descricao' => 'Freightliner'],
            ['descricao' => 'Hino'],
            ['descricao' => 'UD Trucks'],
            ['descricao' => 'Isuzu'],
            ['descricao' => 'SINOTRUK'],
            ['descricao' => 'Western Star'],
            ['descricao' => 'Kamaz'],
            ['descricao' => 'JAC Motors'],
            ['descricao' => 'Mitsubishi Fuso']
        ];

        foreach ($marcas as $marca) {
            MarcaVeiculo::create($marca);
        }
    }
}
