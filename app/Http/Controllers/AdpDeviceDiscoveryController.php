<?php

namespace App\Http\Controllers;

use App\Models\AdpDevice;
use App\Models\AdpIntegradorConfig;
use App\Services\Balanca\AdpDeviceDiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdpDeviceDiscoveryController extends BaseController
{
    private function resolveConfig(Request $request): AdpIntegradorConfig
    {
        $configId = (int) $request->get('integrador_config_id');
        return AdpIntegradorConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->findOrFail($configId);
    }

    private function service(AdpIntegradorConfig $config): AdpDeviceDiscoveryService
    {
        $config->garantirTokenGlobal();

        return new AdpDeviceDiscoveryService([
            'base_url' => $config->base_url,
            'global_token' => $config->global_token_enabled ? $config->global_token : null,
            'global_token_type' => $config->global_token_type ?? 'none',
            'global_token_header' => $config->global_token_header ?? 'X-ADP-API-TOKEN',
            'timeout_ms' => (int) ($config->timeout_ms ?? 5000),
        ]);
    }

    public function status(Request $request) { return response()->json($this->service($this->resolveConfig($request))->testConnection()); }

    public function tokenInfo(Request $request)
    {
        $config = $this->resolveConfig($request);
        $config->garantirTokenGlobal();

        return response()->json([
            'success' => true,
            'token_enabled' => (bool) $config->global_token_enabled,
            'token_type' => $config->global_token_type ?: 'x_adp_api_token',
            'token_header' => $config->global_token_header ?: 'X-ADP-API-TOKEN',
            'token_masked' => $config->tokenMascarado(),
        ]);
    }

    public function regenerateToken(Request $request)
    {
        $config = $this->resolveConfig($request);
        $config->global_token = AdpIntegradorConfig::gerarTokenSeguro();
        $config->global_token_enabled = true;
        $config->global_token_type = $config->global_token_type ?: 'x_adp_api_token';
        $config->global_token_header = $config->global_token_header ?: 'X-ADP-API-TOKEN';
        $config->save();

        Log::warning('Token global ADP regenerado manualmente.', [
            'empresa_id' => $config->empresa_id,
            'integrador_config_id' => $config->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Novo Token Global gerado com sucesso. Atualize integrações externas que usavam o token anterior.',
            'token_masked' => $config->tokenMascarado(),
        ]);
    }

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
