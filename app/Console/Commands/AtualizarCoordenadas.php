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
        $clientes = Cliente::whereNull('latitude')->orWhere('latitude', '')->take(10)->get();

        foreach ($clientes as $cliente) {
            // Verifica se tem o mínimo necessário para buscar no mapa
            if (empty($cliente->logradouro) || empty($cliente->municipio)) {
                $this->warn("Cliente {$cliente->id} pulado: Endereço incompleto.");
                continue;
            }

            // Monta o endereço de forma limpa, ignorando campos vazios
            $partes = array_filter([$cliente->logradouro, $cliente->numero, $cliente->municipio, $cliente->UF, 'Brasil']);
            $endereco = implode(', ', $partes);

            $coords = $this->buscarNoMapa($endereco);

            if ($coords) {
                $cliente->latitude = $coords['lat'];
                $cliente->longitude = $coords['lon'];
                $cliente->save();
                $this->info("Cliente {$cliente->id} atualizado!");
            }
            sleep(2);
        }

        $fornecedores = Fornecedor::whereNull('latitude')->orWhere('latitude', '')->take(10)->get();

        foreach ($fornecedores as $fornecedor) {
            if (empty($fornecedor->logradouro) || empty($fornecedor->municipio)) {
                $this->warn("Fornecedor {$fornecedor->id} pulado: Endereço incompleto.");
                continue;
            }

            $partes = array_filter([$fornecedor->logradouro, $fornecedor->numero, $fornecedor->municipio, $fornecedor->UF, 'Brasil']);
            $endereco = implode(', ', $partes);

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

            // Aumentado o timeout para 15 segundos para evitar o cURL error 28
            $response = Http::withUserAgent('FersoftERP/1.0')->timeout(15)->get($url);

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
