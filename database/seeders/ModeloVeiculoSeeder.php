<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ModeloVeiculo;

class ModeloVeiculoSeeder extends Seeder
{
    public function run()
    {
        $modelos = [
            // Modelos de Automóveis
            ['descricao' => 'Gol'],
            ['descricao' => 'Onix'],
            ['descricao' => 'HB20'],
            ['descricao' => 'Civic'],
            ['descricao' => 'Corolla'],
            ['descricao' => 'Strada'],
            ['descricao' => 'Renegade'],
            ['descricao' => 'Compass'],
            ['descricao' => 'Creta'],
            ['descricao' => 'Duster'],
            ['descricao' => 'Sandero'],
            ['descricao' => 'Argo'],
            ['descricao' => 'Cronos'],
            ['descricao' => 'Kwid'],
            ['descricao' => 'Polo'],
            ['descricao' => 'Virtus'],
            ['descricao' => 'Fit'],
            ['descricao' => 'HR-V'],
            ['descricao' => 'Tracker'],
            ['descricao' => 'S10'],
            ['descricao' => 'Hilux'],
            ['descricao' => 'Ranger'],
            ['descricao' => 'T-Cross'],
            ['descricao' => 'Kicks'],
            ['descricao' => 'EcoSport'],
            ['descricao' => 'Peugeot 208'],
            ['descricao' => 'Citroën C3'],
            ['descricao' => 'Fiat 500'],
            ['descricao' => 'Golf'],
            ['descricao' => 'Passat'],
            ['descricao' => 'Audi A3'],
            ['descricao' => 'BMW X1'],
            ['descricao' => 'Mercedes-Benz Classe C'],
            ['descricao' => 'Volvo XC60'],
            ['descricao' => 'Mitsubishi ASX'],
            ['descricao' => 'Jeep Wrangler'],
            ['descricao' => 'Pajero'],
            ['descricao' => 'Lancer'],
            ['descricao' => 'Subaru Impreza'],
            ['descricao' => 'Jaguar F-Pace'],
            ['descricao' => 'Porsche Cayenne'],
            ['descricao' => 'Mini Cooper'],
            ['descricao' => 'Lexus NX'],
            ['descricao' => 'Chery Tiggo 8'],
            ['descricao' => 'Land Rover Discovery'],
            ['descricao' => 'Hyundai Tucson'],
            ['descricao' => 'Santa Fe'],
            ['descricao' => 'Kia Sportage'],
            ['descricao' => 'Renault Captur'],

            // Modelos de Caminhões
            ['descricao' => 'Scania R450'],
            ['descricao' => 'Volvo FH 540'],
            ['descricao' => 'Mercedes-Benz Actros'],
            ['descricao' => 'MAN TGX'],
            ['descricao' => 'Iveco Stralis'],
            ['descricao' => 'Ford Cargo 2429'],
            ['descricao' => 'DAF XF105'],
            ['descricao' => 'VW Constellation'],
            ['descricao' => 'Mack Anthem'],
            ['descricao' => 'Kenworth T680'],
            ['descricao' => 'International ProStar'],
            ['descricao' => 'Peterbilt 579'],
            ['descricao' => 'Tatra Phoenix'],
            ['descricao' => 'Foton Auman'],
            ['descricao' => 'Hyundai HD78'],
            ['descricao' => 'Renault T High'],
            ['descricao' => 'Freightliner Cascadia'],
            ['descricao' => 'Hino 500 Series'],
            ['descricao' => 'UD Quon'],
            ['descricao' => 'Isuzu NPR'],
            ['descricao' => 'SINOTRUK Howo'],
            ['descricao' => 'Western Star 4700'],
            ['descricao' => 'Kamaz 6520'],
            ['descricao' => 'JAC Motors Sunray'],
            ['descricao' => 'Mitsubishi Fuso Canter'],
            ['descricao' => 'Volvo VM'],
            ['descricao' => 'Scania P320'],
            ['descricao' => 'Ford Cargo 1119'],
            ['descricao' => 'Iveco Daily'],
            ['descricao' => 'DAF CF85'],
            ['descricao' => 'Mercedes-Benz Accelo'],
            ['descricao' => 'Volvo VNL'],
            ['descricao' => 'Scania G410'],
            ['descricao' => 'Kenworth W900'],
            ['descricao' => 'MAN TGS'],
            ['descricao' => 'Hyundai Mighty'],
            ['descricao' => 'Mercedes-Benz Atego'],
            ['descricao' => 'DAF LF'],
            ['descricao' => 'Freightliner Coronado'],
            ['descricao' => 'Peterbilt 386'],
            ['descricao' => 'Isuzu FVR'],
            ['descricao' => 'Tata Prima'],
            ['descricao' => 'Foton BJ3253'],
            ['descricao' => 'Renault Premium Lander'],
            ['descricao' => 'Mack Granite'],
            ['descricao' => 'Iveco Eurocargo'],
            ['descricao' => 'Ford F-350'],
            ['descricao' => 'Scania S730'],
            ['descricao' => 'Mitsubishi Fuso Fighter']
        ];

        foreach ($modelos as $modelo) {
            ModeloVeiculo::create($modelo);
        }
    }
}
