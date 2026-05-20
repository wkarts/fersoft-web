<?php

namespace App\Http\Controllers;

use App\Models\AdpDevice;
use App\Models\AdpCamera;
use App\Models\AdpIntegradorConfig;
use App\Services\Balanca\AdpDeviceDiscoveryService;
use Illuminate\Http\Request;

class AdpDeviceDiscoveryController extends BaseController
{
    protected $redirectPage = '/balancas';
    protected $formTitle = 'Integração ADP';
    protected $resource = 'adp_device_discovery';

    /**
     * O BaseController exige rules() e messages().
     * Este controller usa endpoints JSON próprios e valida cada ação no próprio método.
     */
    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

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
        ], [
            'descricao.required' => 'Informe a descrição da configuração ADP.',
            'base_url.required' => 'Informe a URL base do ADP.',
            'global_token_type.in' => 'Tipo de token global inválido.',
            'timeout_ms.min' => 'O timeout mínimo é de 1000ms.',
            'timeout_ms.max' => 'O timeout máximo é de 60000ms.',
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
                'global_token_masked' => method_exists($config, 'tokenMascarado') ? $config->tokenMascarado() : null,
                'global_token_header' => $config->global_token_header ?? 'X-ADP-API-TOKEN',
                'timeout_ms' => (int) ($config->timeout_ms ?? 5000),
                'ativo' => (bool) $config->ativo,
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
                    'global_token_masked' => method_exists($config, 'tokenMascarado') ? $config->tokenMascarado() : null,
                    'global_token_header' => $config->global_token_header ?? 'X-ADP-API-TOKEN',
                    'timeout_ms' => (int) ($config->timeout_ms ?? 5000),
                    'ativo' => (bool) $config->ativo,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'configs' => $configs,
        ]);
    }

    public function runtimeConfig(Request $request)
    {
        $config = $this->resolveConfig($request);

        return response()->json([
            'success' => true,
            'config' => [
                'id' => $config->id,
                'descricao' => $config->descricao,
                'base_url' => $config->base_url,
                'global_token_enabled' => (bool) $config->global_token_enabled,
                'global_token_type' => $config->global_token_type ?? 'x_adp_api_token',
                'global_token_header' => $config->global_token_header ?? 'X-ADP-API-TOKEN',
                // Necessário somente para modo local/browser: quando a Base URL é localhost/127.0.0.1,
                // quem consegue acessar o ADP é o navegador do operador, não o servidor Laravel.
                'global_token' => $config->global_token_enabled ? $config->global_token : null,
                'timeout_ms' => (int) ($config->timeout_ms ?? 5000),
            ],
        ]);
    }

    private function resolveConfig(Request $request): AdpIntegradorConfig
    {
        $configId = (int) $request->get('integrador_config_id');

        if ($configId <= 0) {
            abort(422, 'Informe ou selecione uma configuração ADP válida.');
        }

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

    public function status(Request $request)
    {
        return response()->json($this->service($this->resolveConfig($request))->testConnection());
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

        return response()->json($this->persistDevices($config, $result['devices'] ?? []));
    }

    public function importDevices(Request $request)
    {
        $config = $this->resolveConfig($request);

        $data = $request->validate([
            'devices' => ['required', 'array'],
            'devices.*.uuid' => ['nullable', 'string', 'max:255'],
            'devices.*.type' => ['nullable', 'string', 'max:50'],
            'devices.*.name' => ['nullable', 'string', 'max:255'],
            'devices.*.model' => ['nullable', 'string', 'max:255'],
            'devices.*.driver' => ['nullable', 'string', 'max:255'],
            'devices.*.protocol' => ['nullable', 'string', 'max:100'],
            'devices.*.host' => ['nullable', 'string', 'max:255'],
            'devices.*.port' => ['nullable'],
            'devices.*.baud_rate' => ['nullable'],
            'devices.*.status' => ['nullable', 'string', 'max:80'],
            'devices.*.supports_stream' => ['nullable', 'boolean'],
            'devices.*.supports_snapshot' => ['nullable', 'boolean'],
            'devices.*.snapshot_url' => ['nullable', 'string'],
            'devices.*.stream_url' => ['nullable', 'string'],
            'devices.*.metadata' => ['nullable'],
            'source' => ['nullable', 'string', 'max:80'],
        ]);

        return response()->json($this->persistDevices($config, $data['devices'] ?? []));
    }

    private function persistDevices(AdpIntegradorConfig $config, array $devices): array
    {
        $now = now();
        $synced = 0;
        $ignored = 0;

        foreach ($devices as $device) {
            $deviceUuid = trim((string) ($device['uuid'] ?? ''));

            if ($deviceUuid === '') {
                $ignored++;
                continue;
            }

            $deviceType = (string) ($device['type'] ?? 'unknown');

            AdpDevice::updateOrCreate(
                [
                    'empresa_id' => $this->empresa_id,
                    'device_uuid' => $deviceUuid,
                ],
                [
                    'integrador_config_id' => $config->id,
                    'device_type' => $deviceType,
                    'name' => $device['name'] ?? null,
                    'model' => $device['model'] ?? null,
                    'driver' => $device['driver'] ?? null,
                    'protocol' => $device['protocol'] ?? null,
                    'host' => $device['host'] ?? null,
                    'port' => isset($device['port']) ? (string) $device['port'] : null,
                    'baud_rate' => isset($device['baud_rate']) && is_numeric($device['baud_rate']) ? (int) $device['baud_rate'] : null,
                    'status' => $device['status'] ?? 'offline',
                    'supports_stream' => (bool) ($device['supports_stream'] ?? false),
                    'supports_snapshot' => (bool) ($device['supports_snapshot'] ?? false),
                    'metadata_json' => $device['metadata'] ?? $device,
                    'last_seen_at' => $now,
                    'ativo' => true,
                ]
            );

            if ($deviceType === 'camera') {
                AdpCamera::updateOrCreate(
                    [
                        'empresa_id' => $this->empresa_id,
                        'camera_uuid' => $deviceUuid,
                    ],
                    [
                        'integrador_config_id' => $config->id,
                        'descricao' => $device['name'] ?? $deviceUuid,
                        'name' => $device['name'] ?? null,
                        'model' => $device['model'] ?? null,
                        'driver' => $device['driver'] ?? null,
                        'protocol' => $device['protocol'] ?? null,
                        'host' => $device['host'] ?? null,
                        'port' => isset($device['port']) ? (string) $device['port'] : null,
                        'stream_url' => $device['stream_url'] ?? null,
                        'snapshot_url' => $device['snapshot_url'] ?? null,
                        'supports_stream' => (bool) ($device['supports_stream'] ?? false),
                        'supports_snapshot' => (bool) ($device['supports_snapshot'] ?? false),
                        'status' => $device['status'] ?? 'offline',
                        'ultimo_status_em' => $now,
                        'ativo' => true,
                        'metadata_json' => $device['metadata'] ?? $device,
                    ]
                );
            }

            $synced++;
        }

        return [
            'success' => true,
            'devices_synced' => $synced,
            'devices_ignored' => $ignored,
            'synced_at' => $now->format('Y-m-d H:i:s'),
        ];
    }
}
