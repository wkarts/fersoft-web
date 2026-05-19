<?php

namespace App\Http\Controllers;

use App\Services\Balanca\AdpDeviceDiscoveryService;
use Illuminate\Http\Request;

class AdpDeviceDiscoveryController extends BaseController
{
    private function service(Request $request): AdpDeviceDiscoveryService
    {
        return new AdpDeviceDiscoveryService([
            'base_url' => $request->get('base_url'),
            'global_token' => $request->get('global_token'),
            'global_token_type' => $request->get('global_token_type', 'none'),
            'global_token_header' => $request->get('global_token_header', 'X-ADP-API-TOKEN'),
            'timeout_ms' => (int) $request->get('timeout_ms', 5000),
        ]);
    }

    public function status(Request $request) { return response()->json($this->service($request)->testConnection()); }
    public function devices(Request $request) { return response()->json($this->service($request)->listDevices()); }
    public function scales(Request $request) { return response()->json($this->service($request)->listScales()); }
    public function cameras(Request $request) { return response()->json($this->service($request)->listCameras()); }
    public function syncDevices(Request $request) { return response()->json($this->service($request)->listDevices()); }
}
