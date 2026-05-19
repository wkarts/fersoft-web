<?php

namespace App\Services\Balanca;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdpDeviceDiscoveryService
{
    public function __construct(private array $config = [])
    {
    }

    public function testConnection(): array
    {
        $response = $this->request('/api/health');

        if (($response['success'] ?? false) === true) {
            return $response;
        }

        // Fallback público/local previsto na documentação.
        // Útil para diferenciar API desligada de token inválido/configuração errada.
        $publicHealth = $this->request('/health', false);

        if (($publicHealth['success'] ?? false) === true) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'API ADP online, porém token inválido ou endpoint protegido recusou acesso.',
                'status_code' => $response['status_code'] ?? null,
                'public_health' => true,
            ];
        }

        return $response;
    }

    public function listDevices(): array
    {
        $scales = $this->listScales();
        $cameras = $this->listCameras();

        $success = (bool) (($scales['success'] ?? false) || ($cameras['success'] ?? false));
        $devices = array_merge($scales['devices'] ?? [], $cameras['devices'] ?? []);

        return [
            'success' => $success,
            'message' => $success ? 'Dispositivos ADP listados com sucesso.' : 'Falha na listagem de dispositivos',
            'devices' => $devices,
            'scales_count' => count($scales['devices'] ?? []),
            'cameras_count' => count($cameras['devices'] ?? []),
            'errors' => array_values(array_filter([
                ($scales['success'] ?? false) ? null : ($scales['message'] ?? 'Falha ao listar balanças'),
                ($cameras['success'] ?? false) ? null : ($cameras['message'] ?? 'Falha ao listar câmeras'),
            ])),
        ];
    }

    public function listScales(): array
    {
        $response = $this->request('/api/scales');

        if (!($response['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Falha ao listar balanças ADP.',
                'devices' => [],
                'raw' => $response,
            ];
        }

        $items = $this->extractList($response, ['scales', 'balancas', 'devices']);

        return [
            'success' => true,
            'devices' => collect($items)
                ->map(fn ($d) => $this->normalizeDevice((array) $d, 'scale'))
                ->filter(fn ($d) => trim((string) ($d['uuid'] ?? '')) !== '')
                ->values()
                ->all(),
        ];
    }

    public function listCameras(): array
    {
        $response = $this->request('/api/cameras');

        if (!($response['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Falha ao listar câmeras ADP.',
                'devices' => [],
                'raw' => $response,
            ];
        }

        $items = $this->extractList($response, ['cameras', 'devices']);

        return [
            'success' => true,
            'devices' => collect($items)
                ->map(fn ($d) => $this->normalizeDevice((array) $d, 'camera'))
                ->filter(fn ($d) => trim((string) ($d['uuid'] ?? '')) !== '')
                ->values()
                ->all(),
        ];
    }

    public function getScale(string $uuid): array
    {
        return $this->getByUuid($uuid, 'scale');
    }

    public function getCamera(string $uuid): array
    {
        return $this->getByUuid($uuid, 'camera');
    }

    public function normalizeScale(array $device): array
    {
        return $this->normalizeDevice($device, 'scale');
    }

    public function normalizeCamera(array $device): array
    {
        return $this->normalizeDevice($device, 'camera');
    }

    private function getByUuid(string $uuid, string $type): array
    {
        $devices = $type === 'camera' ? $this->listCameras()['devices'] : $this->listScales()['devices'];
        $device = collect($devices)->first(fn ($d) => $d['uuid'] === $uuid && $d['type'] === $type);

        return ['success' => (bool) $device, 'device' => $device];
    }

    private function normalizeDevice(array $device, ?string $forcedType = null): array
    {
        $driver = strtolower((string) data_get($device, 'driver', data_get($device, 'driver_name', '')));
        $type = $forcedType ?: data_get($device, 'type', str_contains($driver, 'camera') ? 'camera' : 'scale');

        $uuid = data_get($device, 'uuid',
            data_get($device, 'id',
                data_get($device, 'device_uuid',
                    data_get($device, 'code', '')
                )
            )
        );

        return [
            'uuid' => (string) $uuid,
            'type' => $type,
            'name' => (string) data_get($device, 'name', data_get($device, 'descricao', data_get($device, 'description', 'Dispositivo ADP'))),
            'model' => data_get($device, 'model', data_get($device, 'modelo')),
            'driver' => data_get($device, 'driver', data_get($device, 'driver_name')),
            'protocol' => data_get($device, 'protocol', data_get($device, 'protocolo')),
            'host' => data_get($device, 'host', data_get($device, 'ip')),
            'port' => data_get($device, 'port', data_get($device, 'porta', data_get($device, 'porta_serial'))),
            'baud_rate' => data_get($device, 'baud_rate', data_get($device, 'baudRate', data_get($device, 'velocidade'))),
            'status' => data_get($device, 'status', data_get($device, 'state', 'online')),
            'stable' => (bool) data_get($device, 'stable', data_get($device, 'estavel', false)),
            'last_weight' => (float) data_get($device, 'last_weight', data_get($device, 'weight', data_get($device, 'peso', 0))),
            'unit' => data_get($device, 'unit', data_get($device, 'unidade', 'kg')),
            'clients' => (int) data_get($device, 'clients', data_get($device, 'clientes', 0)),
            'supports_stream' => (bool) data_get($device, 'supports_stream', data_get($device, 'stream', $type === 'camera')),
            'supports_snapshot' => (bool) data_get($device, 'supports_snapshot', data_get($device, 'snapshot', $type === 'camera')),
            'snapshot_url' => data_get($device, 'snapshot_url'),
            'stream_url' => data_get($device, 'stream_url'),
            'metadata' => (array) data_get($device, 'metadata', []),
        ];
    }

    private function request(string $path, bool $withAuth = true): array
    {
        try {
            $url = $this->url($path);
            $http = Http::timeout(($this->config['timeout_ms'] ?? 5000) / 1000)
                ->acceptJson()
                ->withOptions(['verify' => false]);

            $query = [];

            if ($withAuth) {
                [$http, $query] = $this->applyAuth($http);
            }

            $response = $http->get($url, $query);
            $json = $response->json();
            $body = is_array($json) ? $json : [];
            $status = $response->status();

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => $this->statusMessage($status, $body),
                    'status_code' => $status,
                    'url' => $url,
                    'body' => $body,
                ];
            }

            if (!is_array($json)) {
                return [
                    'success' => false,
                    'message' => 'Resposta inválida do ADP.',
                    'status_code' => $status,
                    'url' => $url,
                ];
            }

            // Alguns endpoints retornam somente dados, sem success explícito.
            if (!array_key_exists('success', $body)) {
                $body['success'] = true;
            }

            $body['status_code'] = $status;
            $body['_url'] = $url;

            return $body;
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'ADP offline ou inacessível a partir do servidor Laravel.',
                'error_code' => 'ADP_OFFLINE',
                'exception' => $e->getMessage(),
                'url' => $this->url($path),
            ];
        }
    }

    private function applyAuth(PendingRequest $http): array
    {
        $query = [];
        $type = $this->config['global_token_type'] ?? 'none';
        $token = $this->config['global_token'] ?? null;
        $header = $this->config['global_token_header'] ?? 'X-ADP-API-TOKEN';

        if (!$token || $type === 'none') {
            return [$http, $query];
        }

        if ($type === 'x_adp_api_token') {
            $http = $http->withHeaders([$header ?: 'X-ADP-API-TOKEN' => $token]);
        }

        if ($type === 'bearer') {
            $http = $http->withToken($token);
        }

        if ($type === 'query') {
            $query['token'] = $token;
        }

        return [$http, $query];
    }

    private function url(string $path): string
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        $path = '/' . ltrim($path, '/');

        // Se o usuário salvou http://host:porta/api, não duplica /api.
        if (Str::endsWith($baseUrl, '/api') && Str::startsWith($path, '/api/')) {
            $path = substr($path, 4);
        }

        return $baseUrl . $path;
    }

    private function extractList(array $response, array $keys): array
    {
        if (array_is_list($response)) {
            return $response;
        }

        foreach ($keys as $key) {
            $direct = data_get($response, $key);
            if (is_array($direct)) {
                return $direct;
            }

            $nested = data_get($response, 'data.' . $key);
            if (is_array($nested)) {
                return $nested;
            }
        }

        $data = data_get($response, 'data');
        if (is_array($data) && array_is_list($data)) {
            return $data;
        }

        return [];
    }

    private function statusMessage(int $status, array $body): string
    {
        $message = data_get($body, 'message', data_get($body, 'error'));

        if ($message) {
            return (string) $message;
        }

        return match ($status) {
            401 => 'Token ADP inválido ou ausente.',
            403 => 'Acesso negado pela API ADP.',
            404 => 'Endpoint ADP não encontrado. Verifique se a Base URL aponta para a raiz da API.',
            422 => 'Requisição ADP inválida.',
            default => 'Falha HTTP ' . $status . ' ao comunicar com ADP.',
        };
    }
}
