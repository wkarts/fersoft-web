<?php

namespace App\Services\Balanca;

use App\Models\AdpIntegradorConfig;
use App\Models\BalancaConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BalancaLeitorService
{
    public function status(BalancaConfig $balanca): array { return $this->details($balanca); }
    public function open(BalancaConfig $balanca): array { return $this->adpCall($balanca, '/open', 'post'); }
    public function close(BalancaConfig $balanca): array { return $this->adpCall($balanca, '/close', 'post'); }
    public function read(BalancaConfig $balanca): array { return $this->normalizeWeightResponse($this->adpCall($balanca, '/read'), $balanca); }
    public function details(BalancaConfig $balanca): array { return $this->adpCall($balanca, '/scales/' . ($balanca->adp_scale_uuid ?? $balanca->id)); }

    public function snapshot(BalancaConfig $balanca, array $options = []): array
    {
        $cameras = json_decode((string) ($balanca->adp_camera_uuids ?? '[]'), true) ?: [];
        $payload = ['cameras' => $cameras, 'return_base64' => (bool) ($balanca->snapshot_retorno_base64 ?? false)];
        return $this->normalizeSnapshotResponse($this->adpCall($balanca, '/snapshot', 'post', $payload), $balanca);
    }

    public function captureEvidence(BalancaConfig $balanca, array $options = []): array
    {
        $read = $this->read($balanca);
        if (!($read['success'] ?? false)) return $read;
        if (($balanca->exigir_peso_estavel ?? false) && !($read['estavel'] ?? false)) {
            return ['success' => false, 'balanca_id' => $balanca->id, 'status' => 'oscilando', 'message' => 'Peso oscilando. Aguarde estabilizar.', 'error_code' => 'SCALE_UNSTABLE_WEIGHT'];
        }
        $snapshot = ($balanca->usa_cameras && $balanca->captura_snapshot_automatica) ? $this->snapshot($balanca, $options) : ['snapshots' => []];
        return ['success' => true, 'event_id' => 'PESAGEM-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(6)), 'balanca' => ['id' => $balanca->id, 'descricao' => $balanca->descricao, 'modelo' => $balanca->modelo, 'driver' => $balanca->driver], 'peso' => ['valor' => $read['peso'], 'formatado' => $read['peso_formatado'], 'unidade' => $read['unidade'], 'estavel' => $read['estavel'], 'origem' => 'balanca', 'capturado_em' => $read['read_at']], 'cameras' => $snapshot['snapshots'] ?? [], 'metadata' => ['empresa_id' => $balanca->empresa_id, 'user_id' => auth()->id(), 'ip' => request()->ip(), 'user_agent' => request()->userAgent()]];
    }

    public function buildAuthOptions(BalancaConfig $balanca): array
    {
        $global = null;
        if ($balanca->integrador_config_id) {
            $global = AdpIntegradorConfig::where('empresa_id', $balanca->empresa_id)->find($balanca->integrador_config_id);
        }

        $useConnectionToken = ($balanca->connection_token_enabled ?? false) && !empty($balanca->connection_token);
        if ($useConnectionToken) {
            return ['type' => $balanca->connection_token_type ?: 'x_adp_api_token', 'token' => $balanca->connection_token, 'header' => $balanca->connection_token_header ?: 'X-ADP-API-TOKEN'];
        }

        if ($global && $global->global_token_enabled && $global->global_token) {
            return ['type' => $global->global_token_type ?: 'x_adp_api_token', 'token' => $global->global_token, 'header' => $global->global_token_header ?: 'X-ADP-API-TOKEN'];
        }

        return ['type' => 'none', 'token' => null, 'header' => null];
    }

    public function normalizeWeightResponse(array $response, BalancaConfig $balanca): array
    {
        if (!(bool) data_get($response, 'success', true)) return ['success' => false, 'balanca_id' => $balanca->id, 'status' => 'offline', 'message' => data_get($response, 'message', 'Não foi possível comunicar com a balança.'), 'error_code' => 'SCALE_CONNECTION_ERROR'];
        $peso = (float) data_get($response, 'weight', data_get($response, 'peso', data_get($response, 'data.weight', 0)));
        $estavel = (bool) data_get($response, 'stable', data_get($response, 'estavel', false));
        return ['success' => true, 'balanca_id' => $balanca->id, 'descricao' => $balanca->descricao, 'modelo' => $balanca->modelo, 'driver' => $balanca->driver, 'integrador' => $balanca->integrador ?? 'adp', 'protocolo' => $balanca->protocol ?? 'rs232', 'porta' => $balanca->porta_serial ?? $balanca->port, 'baud_rate' => $balanca->baud_rate ?? $balanca->velocidade, 'clientes' => (int) data_get($response, 'clients', 0), 'peso' => $peso, 'peso_liquido' => $peso, 'peso_formatado' => number_format($peso, 0, ',', '.'), 'unidade' => data_get($response, 'unit', 'kg'), 'tara' => 0, 'estavel' => $estavel, 'sobrecarga' => false, 'status' => data_get($response, 'status', 'online'), 'origem' => 'balanca', 'message' => 'Leitura realizada com sucesso.', 'read_at' => now()->format('Y-m-d H:i:s')];
    }

    public function normalizeSnapshotResponse(array $response, BalancaConfig $balanca): array { return ['success' => (bool) data_get($response, 'success', true), 'snapshots' => data_get($response, 'snapshots', [])]; }

    private function adpCall(BalancaConfig $balanca, string $path, string $method = 'get', array $payload = []): array
    {
        try {
            $baseUrl = rtrim((string) ($balanca->backend_server_address ?? ''), '/');
            $auth = $this->buildAuthOptions($balanca);
            $http = Http::timeout((($balanca->timeout_ms ?? 5000) / 1000))->retry(2, 250)->withOptions(['verify' => false]);
            if (($auth['type'] ?? 'none') === 'x_adp_api_token' && !empty($auth['token'])) $http = $http->withHeaders([($auth['header'] ?? 'X-ADP-API-TOKEN') => $auth['token']]);
            if (($auth['type'] ?? 'none') === 'bearer' && !empty($auth['token'])) $http = $http->withToken($auth['token']);
            if (($auth['type'] ?? 'none') === 'query' && !empty($auth['token'])) $payload['token'] = $auth['token'];
            $resp = $method === 'post' ? $http->post($baseUrl . $path, $payload) : $http->get($baseUrl . $path, $payload);
            return is_array($resp->json()) ? $resp->json() : ['success' => false, 'message' => 'Resposta inválida'];
        } catch (\Throwable $e) {
            \Log::warning('Falha comunicação ADP', ['balanca_id' => $balanca->id, 'empresa_id' => $balanca->empresa_id, 'message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Não foi possível comunicar com a balança.'];
        }
    }
}
