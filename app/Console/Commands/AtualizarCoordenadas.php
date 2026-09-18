<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\Fornecedor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AtualizarCoordenadas extends Command
{
    protected $signature = 'cadastros:atualizar-coordenadas';
    protected $description = 'Atualiza as coordenadas de clientes e fornecedores em lotes pequenos para respeitar a API gratuita.';

    public function handle()
    {
        // Pega 10 clientes que não têm latitude cadastrada
        $clientes = Cliente::whereNull('latitude')->orWhere('latitude', '')->take(10)->get();

        foreach ($clientes as $cliente) {
            $endereco = "{$cliente->logradouro}, {$cliente->numero}, {$cliente->municipio}, {$cliente->UF}, Brasil";
            $coords = $this->buscarNoMapa($endereco);

            if ($coords) {
                $cliente->latitude = $coords['lat'];
                $cliente->longitude = $coords['lon'];
                $cliente->save();
                $this->info("Cliente {$cliente->id} atualizado!");
            }
            // Pausa obrigatória de 2 segundos para o Nominatim não bloquear o seu IP
            sleep(2);
        }

        // Pega 10 fornecedores (mesma lógica)
        $fornecedores = Fornecedor::whereNull('latitude')->orWhere('latitude', '')->take(10)->get();
        foreach ($fornecedores as $fornecedor) {
            $endereco = "{$fornecedor->logradouro}, {$fornecedor->numero}, {$fornecedor->municipio}, {$fornecedor->UF}, Brasil";
            $coords = $this->buscarNoMapa($endereco);

            if ($coords) {
                $fornecedor->latitude = $coords['lat'];
                $fornecedor->longitude = $coords['lon'];
                $fornecedor->save();
                $this->info("Fornecedor {$fornecedor->id} atualizado!");
            }
            sleep(2);
        }
    }

    private function buscarNoMapa($endereco)
    {
        try {
            $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($endereco) . "&limit=1";
            $response = Http::withUserAgent('FersoftERP/1.0')->timeout(5)->get($url);

            if ($response->successful() && count($response->json()) > 0) {
                $data = $response->json()[0];
                return ['lat' => $data['lat'], 'lon' => $data['lon']];
            }
        } catch (\Exception $e) {
            Log::warning("Erro ao buscar coordenadas em lote: " . $e->getMessage());
        }
        return null;
    }
}
