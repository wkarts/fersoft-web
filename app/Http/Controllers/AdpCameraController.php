<?php

namespace App\Http\Controllers;

use App\Models\AdpCamera;
use App\Models\AdpDevice;
use App\Models\AdpIntegradorConfig;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdpCameraController extends BaseController
{
    protected $model = AdpCamera::class;
    protected $redirectPage = '/adp/cameras';
    protected $formTitle = 'Câmeras ADP';
    protected $resource = 'adp_cameras';

    protected function rules(): array
    {
        return [
            'integrador_config_id' => ['required', 'integer'],
            'camera_uuid' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'driver' => ['nullable', 'string', 'max:255'],
            'protocol' => ['nullable', 'string', 'max:100'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'string', 'max:50'],
            'stream_url' => ['nullable', 'string'],
            'snapshot_url' => ['nullable', 'string'],
            'supports_stream' => ['nullable', 'boolean'],
            'supports_snapshot' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:80'],
            'camera_access_mode' => ['nullable', Rule::in(['all', 'selective'])],
            'ativo' => ['nullable', 'boolean'],
            'metadata_json' => ['nullable'],
            'usuarios_permitidos' => ['nullable', 'array'],
            'usuarios_permitidos.*' => ['nullable', 'integer'],
        ];
    }

    protected function messages(): array
    {
        return [
            'integrador_config_id.required' => 'Selecione a configuração ADP.',
            'camera_uuid.required' => 'Informe ou selecione uma câmera ADP.',
            'descricao.required' => 'Informe a descrição da câmera.',
        ];
    }

    public function list(Request $request)
    {
        $cameras = AdpCamera::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->paginate(30);

        $configs = AdpIntegradorConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->orderBy('descricao')
            ->get();

        $usuarios = Usuario::where('empresa_id', $this->empresa_id)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $permissoes = [];
        if (Schema::hasTable('adp_camera_usuario')) {
            $permissoes = DB::table('adp_camera_usuario')
                ->where('empresa_id', $this->empresa_id)
                ->get()
                ->groupBy('adp_camera_id')
                ->map(fn ($rows) => $rows->pluck('usuario_id')->map(fn ($id) => (int) $id)->values()->all())
                ->all();
        }

        return view('adp_cameras.list', [
            'title' => $this->formTitle,
            'cameras' => $cameras,
            'configs' => $configs,
            'configMap' => $configs->keyBy('id'),
            'usuarios' => $usuarios,
            'permissoes' => $permissoes,
        ]);
    }


    public function parametros(Request $request)
    {
        $cameras = AdpCamera::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->paginate(30);

        $configs = AdpIntegradorConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->orderBy('descricao')
            ->get();

        $usuarios = Usuario::where('empresa_id', $this->empresa_id)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $permissoes = [];
        if (Schema::hasTable('adp_camera_usuario')) {
            $permissoes = DB::table('adp_camera_usuario')
                ->where('empresa_id', $this->empresa_id)
                ->get()
                ->groupBy('adp_camera_id')
                ->map(fn ($rows) => $rows->pluck('usuario_id')->map(fn ($id) => (int) $id)->values()->all())
                ->all();
        }

        return view('adp_cameras.parametros', [
            'title' => 'Parâmetros de Câmeras ADP',
            'cameras' => $cameras,
            'configs' => $configs,
            'configMap' => $configs->keyBy('id'),
            'usuarios' => $usuarios,
            'permissoes' => $permissoes,
        ]);
    }

    public function save(Request $request, $id = null)
    {
        $data = $request->validate($this->rules(), $this->messages());

        $configOk = AdpIntegradorConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->where('id', (int) $data['integrador_config_id'])
            ->exists();

        if (!$configOk) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'Configuração ADP inválida para esta empresa.');
        }

        $metadata = $data['metadata_json'] ?? null;
        if (is_string($metadata) && trim($metadata) !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = json_last_error() === JSON_ERROR_NONE ? $decoded : ['raw' => $metadata];
        }

        $cameraUuid = trim((string) $data['camera_uuid']);
        $config = AdpIntegradorConfig::where('empresa_id', $this->empresa_id)
            ->where('id', (int) $data['integrador_config_id'])
            ->first();
        $baseUrl = $config ? rtrim((string) $config->base_url, '/') : '';
        $streamUrl = trim((string) ($data['stream_url'] ?? ''));
        $snapshotUrl = trim((string) ($data['snapshot_url'] ?? ''));

        if ($baseUrl !== '' && $cameraUuid !== '') {
            $streamUrl = $streamUrl !== '' ? $streamUrl : $this->cameraEndpoint($baseUrl, $cameraUuid, 'stream');
            $snapshotUrl = $snapshotUrl !== '' ? $snapshotUrl : $this->cameraEndpoint($baseUrl, $cameraUuid, 'snapshot');
        }

        $payload = [
            'empresa_id' => $this->empresa_id,
            'integrador_config_id' => (int) $data['integrador_config_id'],
            'camera_uuid' => $cameraUuid,
            'descricao' => trim((string) $data['descricao']),
            'name' => $data['name'] ?? null,
            'model' => $data['model'] ?? null,
            'driver' => $data['driver'] ?? null,
            'protocol' => $data['protocol'] ?? null,
            'host' => $data['host'] ?? null,
            'port' => isset($data['port']) ? (string) $data['port'] : null,
            'stream_url' => $streamUrl ?: null,
            'snapshot_url' => $snapshotUrl ?: null,
            'supports_stream' => (bool) ($data['supports_stream'] ?? false),
            'supports_snapshot' => (bool) ($data['supports_snapshot'] ?? false),
            'status' => $data['status'] ?? null,
            'camera_access_mode' => $data['camera_access_mode'] ?? 'all',
            'ativo' => array_key_exists('ativo', $data) ? (bool) $data['ativo'] : true,
            'metadata_json' => is_array($metadata) ? $metadata : [],
        ];

        try {
            if ($id) {
                $camera = AdpCamera::where('empresa_id', $this->empresa_id)->findOrFail($id);
                $camera->update($payload);
            } else {
                $camera = AdpCamera::updateOrCreate(
                    ['empresa_id' => $this->empresa_id, 'camera_uuid' => $payload['camera_uuid']],
                    $payload
                );
            }

            $this->syncUsuariosPermitidos($camera, $data['usuarios_permitidos'] ?? [], $payload['camera_access_mode'] ?? 'all');

            return redirect()->route('adp.cameras.list')->with('mensagem_sucesso', 'Câmera ADP salva com sucesso.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'Erro ao salvar câmera ADP: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $camera = AdpCamera::where('empresa_id', $this->empresa_id)->findOrFail($id);
            if (Schema::hasTable('adp_camera_usuario')) {
                DB::table('adp_camera_usuario')->where('empresa_id', $this->empresa_id)->where('adp_camera_id', $camera->id)->delete();
            }
            $camera->delete();
            return redirect()->route('adp.cameras.list')->with('mensagem_sucesso', 'Câmera ADP removida com sucesso.');
        } catch (\Throwable $e) {
            return redirect()->route('adp.cameras.list')->with('mensagem_erro', 'Erro ao remover câmera ADP: ' . $e->getMessage());
        }
    }


    public function snapshotPreview(Request $request)
    {
        $data = $request->validate([
            'response' => ['nullable'],
            'file_path' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string'],
            'image_data_url' => ['nullable', 'string'],
        ]);

        $response = $data['response'] ?? [];
        if (is_string($response)) {
            $decoded = json_decode($response, true);
            $response = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($response)) {
            $response = [];
        }

        $dataUrl = $this->extractDataUrlFromSnapshotPreview($data['image_data_url'] ?? null, $response);
        if ($dataUrl) {
            return response()->json(['success' => true, 'image_data_url' => $dataUrl]);
        }

        $filePath = $data['file_path'] ?? data_get($response, 'snapshot.file_path') ?? data_get($response, 'response.snapshot.file_path') ?? data_get($response, 'file_path');
        $filePath = is_string($filePath) ? trim($filePath) : '';
        if ($filePath !== '' && is_file($filePath)) {
            $content = function_exists('safe_get_file_content') ? safe_get_file_content($filePath) : @file_get_contents($filePath);
            if ($content !== false && $content !== null && $content !== '') {
                $mime = function_exists('mime_content_type') ? (@mime_content_type($filePath) ?: 'image/jpeg') : 'image/jpeg';
                return response()->json([
                    'success' => true,
                    'image_data_url' => 'data:' . $mime . ';base64,' . base64_encode($content),
                ]);
            }
        }

        $url = $data['image_url'] ?? data_get($response, 'snapshot.image_url') ?? data_get($response, 'image_url') ?? data_get($response, 'snapshot_url');
        if (is_string($url) && preg_match('~^https?://~i', $url)) {
            return response()->json(['success' => true, 'image_url' => $url]);
        }

        return response()->json(['success' => false, 'message' => 'Snapshot capturado, mas não foi possível resolver a imagem para preview.'], 422);
    }

    private function extractDataUrlFromSnapshotPreview($direct, array $response): ?string
    {
        $candidates = [];
        if (is_string($direct) && trim($direct) !== '') {
            $candidates[] = trim($direct);
        }
        foreach (['image_data_url', 'data_url', 'image_base64', 'base64', 'snapshot.base64', 'snapshot.image_base64', 'snapshot.image_data_url', 'response.snapshot.base64'] as $path) {
            $value = data_get($response, $path);
            if (is_string($value) && trim($value) !== '') {
                $candidates[] = trim($value);
            }
        }
        foreach ($candidates as $value) {
            if (str_starts_with($value, 'data:image')) {
                return $value;
            }
            if (strlen($value) > 200 && !preg_match('~^https?://~i', $value)) {
                return 'data:image/jpeg;base64,' . preg_replace('~^data:image/[^;]+;base64,~', '', $value);
            }
        }
        return null;
    }

    public function importFromDevices(Request $request)
    {
        $devices = AdpDevice::where('empresa_id', $this->empresa_id)
            ->where('device_type', 'camera')
            ->where('ativo', true)
            ->get();

        $total = 0;
        $configs = AdpIntegradorConfig::where('empresa_id', $this->empresa_id)->get()->keyBy('id');

        foreach ($devices as $device) {
            $cfg = $configs->get($device->integrador_config_id);
            $baseUrl = $cfg ? rtrim((string) $cfg->base_url, '/') : '';
            $streamUrl = $device->stream_url;
            $snapshotUrl = $device->snapshot_url;

            if ($baseUrl !== '' && $device->device_uuid) {
                $streamUrl = $streamUrl ?: $this->cameraEndpoint($baseUrl, $device->device_uuid, 'stream');
                $snapshotUrl = $snapshotUrl ?: $this->cameraEndpoint($baseUrl, $device->device_uuid, 'snapshot');
            }

            AdpCamera::updateOrCreate(
                ['empresa_id' => $this->empresa_id, 'camera_uuid' => $device->device_uuid],
                [
                    'integrador_config_id' => $device->integrador_config_id,
                    'descricao' => $device->name ?: $device->device_uuid,
                    'name' => $device->name,
                    'model' => $device->model,
                    'driver' => $device->driver,
                    'protocol' => $device->protocol,
                    'host' => $device->host,
                    'port' => $device->port,
                    'stream_url' => $streamUrl,
                    'snapshot_url' => $snapshotUrl,
                    'supports_stream' => (bool) $device->supports_stream,
                    'supports_snapshot' => (bool) $device->supports_snapshot,
                    'status' => $device->status,
                    'camera_access_mode' => 'all',
                    'ultimo_status_em' => now(),
                    'ativo' => true,
                    'metadata_json' => $device->metadata_json ?: [],
                ]
            );
            $total++;
        }

        return redirect()->route('adp.cameras.list')->with('mensagem_sucesso', $total . ' câmera(s) importada(s) dos dispositivos ADP.');
    }

    private function cameraEndpoint(string $baseUrl, string $uuid, string $action): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        $path = '/api/cameras/' . rawurlencode($uuid) . '/' . $action;

        if (preg_match('~/api$~i', $baseUrl)) {
            $path = preg_replace('~^/api~', '', $path);
        }

        return $baseUrl . $path;
    }

    private function syncUsuariosPermitidos(AdpCamera $camera, array $usuarios, string $accessMode = 'all'): void
    {
        if (!Schema::hasTable('adp_camera_usuario')) {
            return;
        }

        DB::table('adp_camera_usuario')
            ->where('empresa_id', $this->empresa_id)
            ->where('adp_camera_id', $camera->id)
            ->delete();

        if ($accessMode !== 'selective') {
            return;
        }

        $ids = collect($usuarios)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        foreach ($ids as $usuarioId) {
            DB::table('adp_camera_usuario')->insert([
                'empresa_id' => $this->empresa_id,
                'adp_camera_id' => $camera->id,
                'usuario_id' => $usuarioId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
