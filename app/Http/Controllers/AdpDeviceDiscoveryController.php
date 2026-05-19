<?php

namespace App\Http\Controllers;

use App\Models\AdpIntegradorConfig;
use App\Services\Balanca\AdpDeviceDiscoveryService;
use Illuminate\Http\Request;

class AdpDeviceDiscoveryController extends BaseController
{
    private function service(Request $request): AdpDeviceDiscoveryService
    {
        $configId = (int) $request->get('integrador_config_id');
        $config = AdpIntegradorConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->findOrFail($configId);

        return new AdpDeviceDiscoveryService([
            'base_url' => $config->base_url,
            'global_token' => $config->global_token_enabled ? $config->global_token : null,
            'global_token_type' => $config->global_token_type ?? 'none',
            'global_token_header' => $config->global_token_header ?? 'X-ADP-API-TOKEN',
            'timeout_ms' => (int) ($config->timeout_ms ?? 5000),
        ]);
    }

    public function status(Request $request) { return response()->json($this->service($request)->testConnection()); }
    public function devices(Request $request) { return response()->json($this->service($request)->listDevices()); }
    public function scales(Request $request) { return response()->json($this->service($request)->listScales()); }
    public function cameras(Request $request) { return response()->json($this->service($request)->listCameras()); }
    public function syncDevices(Request $request) { return response()->json($this->service($request)->listDevices()); }
}
