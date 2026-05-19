<?php

namespace App\Http\Controllers;

use App\Models\AdpDevice;
use App\Models\AdpIntegradorConfig;
use App\Services\Balanca\AdpDeviceDiscoveryService;
use Illuminate\Http\Request;

class AdpDeviceDiscoveryController extends BaseController
{
    public function salvarConfig(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'descricao' => ['required', 'string', 'max:120'],
            'base_url' => ['required', 'string', 'max:1000'],
            'global_token' => ['nullable', 'string', 'max:2048'],
            'global_token_enabled' => ['nullable', 'boolean'],
            'global_token_type' => ['nullable', 'in:none,x_adp_api_token,bearer,query'],
            'global_token_header' => ['nullable', 'string', 'max:120'],
            'timeout_ms' => ['nullable', 'integer', 'min:1000', 'max:60000'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $config = null;
        if (!empty($data['id'])) {
            $config = AdpIntegradorConfig::where('empresa_id', $this->empresa_id)->findOrFail((int) $data['id']);
        } else {
            $config = new AdpIntegradorConfig();
            $config->empresa_id = $this->empresa_id;
        }

        $config->descricao = $data['descricao'];
        $config->base_url = rtrim($data['base_url'], '/');
        $config->global_token_enabled = (bool) ($data['global_token_enabled'] ?? true);
        $config->global_token_type = $data['global_token_type'] ?? 'x_adp_api_token';
        $config->global_token_header = $data['global_token_header'] ?? 'X-ADP-API-TOKEN';
        $config->timeout_ms = (int) ($data['timeout_ms'] ?? 5000);
        $config->ativo = array_key_exists('ativo', $data) ? (bool) $data['ativo'] : true;

        // Regra: token vazio em edição mantém o token anterior.
        if (array_key_exists('global_token', $data) && trim((string) $data['global_token']) !== '') {
            $config->global_token = trim((string) $data['global_token']);
        }

        $config->save();

        return response()->json([
            'success' => true,
            'message' => 'Configuração ADP salva com sucesso.',
            'config' => [
                'id' => $config->id,
                'descricao' => $config->descricao,
                'base_url' => $config->base_url,
                'global_token_enabled' => (bool) $config->global_token_enabled,
                'global_token_type' => $config->global_token_type ?? 'none',
                'global_token_masked' => $config->tokenMascarado(),
            ],
        ]);
    }

    public function configuracoes()
    {
        $configs = AdpIntegradorConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function (AdpIntegradorConfig $config) {
                return [
                    'id' => $config->id,
                    'descricao' => $config->descricao,
                    'base_url' => $config->base_url,
                    'global_token_enabled' => (bool) $config->global_token_enabled,
                    'global_token_type' => $config->global_token_type ?? 'none',
                    'global_token_masked' => $config->tokenMascarado(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'configs' => $configs,
        ]);
    }

    private function resolveConfig(Request $request): AdpIntegradorConfig
    {
        $configId = (int) $request->get('integrador_config_id');
        return AdpIntegradorConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->findOrFail($configId);
    }

    private function service(AdpIntegradorConfig $config): AdpDeviceDiscoveryService
    {
        return new AdpDeviceDiscoveryService([
            'base_url' => $config->base_url,
            'global_token' => $config->global_token_enabled ? $config->global_token : null,
            'global_token_type' => $config->global_token_type ?? 'none',
            'global_token_header' => $config->global_token_header ?? 'X-ADP-API-TOKEN',
            'timeout_ms' => (int) ($config->timeout_ms ?? 5000),
        ]);
    }

    public function status(Request $request) { return response()->json($this->service($this->resolveConfig($request))->testConnection()); }

    public function devices(Request $request)
    {
        return response()->json($this->service($this->resolveConfig($request))->listDevices());
    }

    public function scales(Request $request)
    {
        return response()->json($this->service($this->resolveConfig($request))->listScales());
    }

    public function cameras(Request $request)
    {
        return response()->json($this->service($this->resolveConfig($request))->listCameras());
    }

    public function syncDevices(Request $request)
    {
        $config = $this->resolveConfig($request);
        $result = $this->service($config)->listDevices();

        if (!($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        $now = now();
        foreach (($result['devices'] ?? []) as $device) {
            AdpDevice::updateOrCreate(
                [
                    'empresa_id' => $this->empresa_id,
                    'device_uuid' => (string) ($device['uuid'] ?? ''),
                ],
                [
                    'integrador_config_id' => $config->id,
                    'device_type' => (string) ($device['type'] ?? 'unknown'),
                    'name' => $device['name'] ?? null,
                    'model' => $device['model'] ?? null,
                    'driver' => $device['driver'] ?? null,
                    'protocol' => $device['protocol'] ?? null,
                    'host' => $device['host'] ?? null,
                    'port' => isset($device['port']) ? (string) $device['port'] : null,
                    'baud_rate' => isset($device['baud_rate']) ? (int) $device['baud_rate'] : null,
                    'status' => $device['status'] ?? 'offline',
                    'supports_stream' => (bool) ($device['supports_stream'] ?? false),
                    'supports_snapshot' => (bool) ($device['supports_snapshot'] ?? false),
                    'metadata_json' => $device['metadata'] ?? [],
                    'last_seen_at' => $now,
                    'ativo' => true,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'devices_synced' => count($result['devices'] ?? []),
            'synced_at' => $now->format('Y-m-d H:i:s'),
        ]);
    }
}
