<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait GeocodeTrait
{
    /**
     * Busca Latitude e Longitude usando o OpenStreetMap.
     * Faz tentativas em "cascata" para garantir que ache mesmo em cidades do interior.
     */
    public function buscarCoordenadas($enderecoCompleto, $cidade = '', $uf = '')
    {
        if (empty($enderecoCompleto)) {
            return ['latitude' => null, 'longitude' => null];
        }

        try {
            // Tentativa 1: Endereço completo
            $coords = $this->fazerRequisicaoOSM($enderecoCompleto);
            if ($coords) return $coords;

            // Tentativa 2: Apenas Cidade e Estado (Útil para interiores menores)
            if (!empty($cidade) && !empty($uf)) {
                $coords = $this->fazerRequisicaoOSM("{$cidade}, {$uf}, Brasil");
                if ($coords) return $coords;
            }

        } catch (\Exception $e) {
            Log::error("Erro no Geocoding: " . $e->getMessage());
        }

        return ['latitude' => null, 'longitude' => null];
    }

    private function fazerRequisicaoOSM($query)
    {
        $response = Http::withHeaders([
            'User-Agent' => 'FersoftERP/1.0'
        ])->timeout(5)->get('https://nominatim.openstreetmap.org/search', [
            'format' => 'json',
            'q' => $query,
            'limit' => 1
        ]);

        if ($response->successful() && count($response->json()) > 0) {
            $dados = $response->json()[0];
            return [
                'latitude' => $dados['lat'],
                'longitude' => $dados['lon']
            ];
        }
        return null;
    }
}
