<?php

namespace App\Services\Balanca;

use Illuminate\Support\Facades\Http;

class AdpDeviceDiscoveryService
{
    public function __construct(private array $config = [])
    {
    }

    public function testConnection(): array
    {
        return $this->request('/devices');
    }

    public function listDevices(): array
    {
        $response = $this->request('/devices');
        $devices = data_get($response, 'data.devices', data_get($response, 'devices', []));

        return [
            'success' => (bool) data_get($response, 'success', true),
            'devices' => collect($devices)->map(fn ($d) => $this->normalizeDevice((array) $d))->values()->all(),
        ];
    }

    public function listScales(): array
    {
        $devices = $this->listDevices();
        return ['success' => $devices['success'], 'devices' => array_values(array_filter($devices['devices'], fn ($d) => $d['type'] === 'scale'))];
    }

    public function listCameras(): array
    {
        $devices = $this->listDevices();
        return ['success' => $devices['success'], 'devices' => array_values(array_filter($devices['devices'], fn ($d) => $d['type'] === 'camera'))];
    }

    public function getScale(string $uuid): array { return $this->getByUuid($uuid, 'scale'); }
    public function getCamera(string $uuid): array { return $this->getByUuid($uuid, 'camera'); }

    public function normalizeScale(array $device): array { return $this->normalizeDevice($device); }
    public function normalizeCamera(array $device): array { return $this->normalizeDevice($device); }

    private function getByUuid(string $uuid, string $type): array
    {
        $devices = $this->listDevices()['devices'];
        $device = collect($devices)->first(fn ($d) => $d['uuid'] === $uuid && $d['type'] === $type);
        return ['success' => (bool) $device, 'device' => $device];
    }

    private function normalizeDevice(array $device): array
    {
        $type = data_get($device, 'type', str_contains(strtolower((string) data_get($device, 'driver', '')), 'camera') ? 'camera' : 'scale');
        return [
            'uuid' => (string) data_get($device, 'uuid', data_get($device, 'id')),
            'type' => $type,
            'name' => (string) data_get($device, 'name', data_get($device, 'descricao', 'Dispositivo ADP')),
            'model' => data_get($device, 'model'),
            'driver' => data_get($device, 'driver'),
            'protocol' => data_get($device, 'protocol'),
            'host' => data_get($device, 'host'),
            'port' => data_get($device, 'port'),
            'baud_rate' => data_get($device, 'baud_rate'),
            'status' => data_get($device, 'status', 'offline'),
            'stable' => (bool) data_get($device, 'stable', false),
            'last_weight' => (float) data_get($device, 'last_weight', 0),
            'unit' => data_get($device, 'unit', 'kg'),
            'clients' => (int) data_get($device, 'clients', 0),
            'supports_stream' => (bool) data_get($device, 'supports_stream', false),
            'supports_snapshot' => (bool) data_get($device, 'supports_snapshot', false),
            'snapshot_url' => data_get($device, 'snapshot_url'),
            'stream_url' => data_get($device, 'stream_url'),
            'metadata' => (array) data_get($device, 'metadata', []),
        ];
    }

    private function request(string $path): array
    {
        try {
            $http = Http::timeout(($this->config['timeout_ms'] ?? 5000) / 1000)->withOptions(['verify' => false]);
            [$http, $query] = $this->applyAuth($http);
            $response = $http->get(rtrim((string) ($this->config['base_url'] ?? ''), '/') . $path, $query)->json();
            return is_array($response) ? $response : ['success' => false, 'message' => 'Resposta inválida do ADP'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'ADP offline', 'error_code' => 'ADP_OFFLINE'];
        }
    }

    private function applyAuth($http): array
    {
        $query = [];
        $type = $this->config['global_token_type'] ?? 'none';
        $token = $this->config['global_token'] ?? null;
        $header = $this->config['global_token_header'] ?? 'X-ADP-API-TOKEN';
        if (!$token) return [$http, $query];
        if ($type === 'x_adp_api_token') $http = $http->withHeaders([$header => $token]);
        if ($type === 'bearer') $http = $http->withToken($token);
        if ($type === 'query') $query['token'] = $token;
        return [$http, $query];
    }
}
