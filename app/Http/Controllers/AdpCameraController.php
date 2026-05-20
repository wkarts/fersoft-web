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

        $payload = [
            'empresa_id' => $this->empresa_id,
            'integrador_config_id' => (int) $data['integrador_config_id'],
            'camera_uuid' => trim((string) $data['camera_uuid']),
            'descricao' => trim((string) $data['descricao']),
            'name' => $data['name'] ?? null,
            'model' => $data['model'] ?? null,
            'driver' => $data['driver'] ?? null,
            'protocol' => $data['protocol'] ?? null,
            'host' => $data['host'] ?? null,
            'port' => isset($data['port']) ? (string) $data['port'] : null,
            'stream_url' => $data['stream_url'] ?? null,
            'snapshot_url' => $data['snapshot_url'] ?? null,
            'supports_stream' => (bool) ($data['supports_stream'] ?? false),
            'supports_snapshot' => (bool) ($data['supports_snapshot'] ?? false),
            'status' => $data['status'] ?? null,
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

            $this->syncUsuariosPermitidos($camera, $data['usuarios_permitidos'] ?? []);

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

    public function importFromDevices(Request $request)
    {
        $devices = AdpDevice::where('empresa_id', $this->empresa_id)
            ->where('device_type', 'camera')
            ->where('ativo', true)
            ->get();

        $total = 0;
        foreach ($devices as $device) {
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
                    'supports_stream' => (bool) $device->supports_stream,
                    'supports_snapshot' => (bool) $device->supports_snapshot,
                    'status' => $device->status,
                    'ultimo_status_em' => now(),
                    'ativo' => true,
                    'metadata_json' => $device->metadata_json ?: [],
                ]
            );
            $total++;
        }

        return redirect()->route('adp.cameras.list')->with('mensagem_sucesso', $total . ' câmera(s) importada(s) dos dispositivos ADP.');
    }

    private function syncUsuariosPermitidos(AdpCamera $camera, array $usuarios): void
    {
        if (!Schema::hasTable('adp_camera_usuario')) {
            return;
        }

        $ids = collect($usuarios)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        DB::table('adp_camera_usuario')
            ->where('empresa_id', $this->empresa_id)
            ->where('adp_camera_id', $camera->id)
            ->delete();

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
