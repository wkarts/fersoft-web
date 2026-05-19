<?php

namespace App\Services\Balanca;

use App\Models\BalancaConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BalancaLeitorService
{
    public function status(BalancaConfig $balanca): array { return $this->details($balanca); }
    public function open(BalancaConfig $balanca): array { return $this->adpCall($balanca, '/open', 'post'); }
    public function close(BalancaConfig $balanca): array { return $this->adpCall($balanca, '/close', 'post'); }

    public function read(BalancaConfig $balanca): array
    {
        $r = $this->adpCall($balanca, '/read');
        return $this->normalizeWeightResponse($r, $balanca);
    }

    public function details(BalancaConfig $balanca): array
    {
        $path = '/scales/' . ($balanca->adp_scale_uuid ?? $balanca->id);
        return $this->adpCall($balanca, $path);
    }

    public function snapshot(BalancaConfig $balanca, array $options = []): array
    {
        $cameras = json_decode((string) ($balanca->adp_camera_uuids ?? '[]'), true) ?: [];
        $payload = ['cameras' => $cameras, 'return_base64' => (bool) ($balanca->snapshot_retorno_base64 ?? false)];
        $r = $this->adpCall($balanca, '/snapshot', 'post', $payload);
        return $this->normalizeSnapshotResponse($r, $balanca);
    }

    public function captureEvidence(BalancaConfig $balanca, array $options = []): array
    {
        $read = $this->read($balanca);
        if (!($read['success'] ?? false)) return $read;
        $snapshot = $this->snapshot($balanca, $options);
        return [
            'success' => true,
            'event_id' => 'PESAGEM-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(6)),
            'balanca' => ['id' => $balanca->id, 'descricao' => $balanca->descricao, 'modelo' => $balanca->modelo, 'driver' => $balanca->driver],
            'peso' => ['valor' => $read['peso'], 'formatado' => $read['peso_formatado'], 'unidade' => $read['unidade'], 'estavel' => $read['estavel'], 'origem' => 'balanca', 'capturado_em' => $read['read_at']],
            'cameras' => $snapshot['snapshots'] ?? [],
            'metadata' => ['empresa_id' => $balanca->empresa_id, 'user_id' => auth()->id(), 'ip' => request()->ip(), 'user_agent' => request()->userAgent()],
        ];
    }

    public function buildAuthOptions(BalancaConfig $balanca): array { return []; }

    public function normalizeWeightResponse(array $response, BalancaConfig $balanca): array
    {
        if (!(bool) data_get($response, 'success', true)) {
            return ['success' => false, 'balanca_id' => $balanca->id, 'status' => 'offline', 'message' => data_get($response, 'message', 'Não foi possível comunicar com a balança.'), 'error_code' => 'SCALE_CONNECTION_ERROR'];
        }
        $peso = (float) data_get($response, 'weight', data_get($response, 'peso', data_get($response, 'data.weight', 0)));
        $estavel = (bool) data_get($response, 'stable', data_get($response, 'estavel', false));
        return [
            'success' => true, 'balanca_id' => $balanca->id, 'descricao' => $balanca->descricao, 'modelo' => $balanca->modelo, 'driver' => $balanca->driver,
            'integrador' => $balanca->integrador ?? 'adp', 'protocolo' => $balanca->protocol ?? 'rs232', 'porta' => $balanca->porta_serial ?? $balanca->port,
            'baud_rate' => $balanca->baud_rate ?? $balanca->velocidade, 'clientes' => (int) data_get($response, 'clients', 0), 'peso' => $peso,
            'peso_liquido' => $peso, 'peso_formatado' => number_format($peso, 0, ',', '.'), 'unidade' => data_get($response, 'unit', 'kg'),
            'tara' => 0, 'estavel' => $estavel, 'sobrecarga' => false, 'status' => data_get($response, 'status', 'online'),
            'origem' => 'balanca', 'message' => 'Leitura realizada com sucesso.', 'read_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    public function normalizeSnapshotResponse(array $response, BalancaConfig $balanca): array
    {
        $shots = data_get($response, 'snapshots', []);
        return ['success' => (bool) data_get($response, 'success', true), 'snapshots' => $shots];
    }

    private function adpCall(BalancaConfig $balanca, string $path, string $method = 'get', array $payload = []): array
    {
        try {
            $http = Http::timeout((($balanca->timeout_ms ?? 5000) / 1000))->withOptions(['verify' => false]);
            $url = rtrim((string) ($balanca->backend_server_address ?? ''), '/') . $path;
            $resp = $method === 'post' ? $http->post($url, $payload) : $http->get($url, $payload);
            $json = $resp->json();
            return is_array($json) ? $json : ['success' => false, 'message' => 'Resposta inválida'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Não foi possível comunicar com a balança.'];
        }
    }
}
