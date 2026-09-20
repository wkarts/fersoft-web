<?php

namespace App\Http\Controllers;

use App\Models\ConnectApiInstance;
use App\Models\Empresa;
use App\Services\ConnectApi\ConnectApiClient;
use App\Services\ConnectApi\ConnectApiInstanceService;
use App\Services\ConnectApi\ConnectApiMessageService;
use Illuminate\Http\Request;

class ConnectApiInstanceController extends BaseController
{
    public function index()
    {
        $user = session('user_logged', []);
        $isSuper = (bool) ($user['super'] ?? false);

        $query = ConnectApiInstance::query()
            ->with('empresa')
            ->whereNull('deleted_at');

        if (!$isSuper) {
            $query->where('empresa_id', $this->empresa_id);
        }

        $records = $query->orderBy('empresa_id')->get();

        return view('connect_api.index', compact('records', 'isSuper'));
    }

    public function companies(Request $request)
    {
        $this->requireSuper();

        $term = trim((string) $request->get('term', ''));

        return response()->json(
            Empresa::query()
                ->where('status', 1)
                ->when($term !== '', function ($query) use ($term) {
                    $query->where(function ($sub) use ($term) {
                        $sub->where('nome', 'like', "%{$term}%")
                            ->orWhere('nome_fantasia', 'like', "%{$term}%")
                            ->orWhere('cnpj', 'like', "%{$term}%");
                    });
                })
                ->limit(50)
                ->get(['id', 'nome', 'nome_fantasia', 'cnpj'])
        );
    }

    public function provision(
        int $empresa,
        ConnectApiInstanceService $service
    ) {
        $this->requireSuper();

        $model = Empresa::findOrFail($empresa);
        $instance = $service->provision($model, $this->usuario_id, $this->filial_id);

        return response()->json([
            'success' => true,
            'instance' => $this->present($instance),
        ]);
    }

    public function reprovision(int $id, ConnectApiInstanceService $service)
    {
        $this->requireSuper();
        $instance = $this->owned($id, true);
        $instance = $service->reprovision($instance, $this->usuario_id);

        return response()->json([
            'success' => true,
            'instance' => $this->present($instance),
        ]);
    }

    public function status(int $id, ConnectApiInstanceService $service)
    {
        $instance = $this->owned($id);
        $instance = $service->refreshStatus($instance);

        return response()->json([
            'success' => true,
            'instance' => $this->present($instance),
        ]);
    }

    public function qr(int $id, ConnectApiInstanceService $service)
    {
        $instance = $this->owned($id);
        $response = $service->pairing($instance);

        return response()->json($response, ($response['success'] ?? false) ? 200 : 502);
    }

    public function pairingCode(
        int $id,
        Request $request,
        ConnectApiInstanceService $service
    ) {
        $instance = $this->owned($id);
        $number = preg_replace('/\D+/', '', (string) $request->input('number', ''));

        if ($number === '') {
            return response()->json(['success' => false, 'error' => 'Informe o número do WhatsApp.'], 422);
        }

        $response = $service->pairing($instance, $number);

        return response()->json($response, ($response['success'] ?? false) ? 200 : 502);
    }

    public function restart(int $id, ConnectApiClient $client)
    {
        $instance = $this->owned($id);

        return response()->json($client->restart($instance));
    }

    public function disconnect(int $id, ConnectApiClient $client)
    {
        $instance = $this->owned($id);
        $response = $client->logout($instance);

        if ($response['success'] ?? false) {
            $instance->connection_status = 'close';
            $instance->disconnected_at = now();
            $instance->save();
        }

        return response()->json($response);
    }

    public function block(int $id)
    {
        $this->requireSuper();
        $instance = $this->owned($id, true);
        $instance->is_blocked = true;
        $instance->save();

        return response()->json(['success' => true]);
    }

    public function unblock(int $id)
    {
        $this->requireSuper();
        $instance = $this->owned($id, true);
        $instance->is_blocked = false;
        $instance->save();

        return response()->json(['success' => true]);
    }

    public function testMessage(int $id, Request $request, ConnectApiMessageService $messages)
    {
        $instance = $this->owned($id);
        $number = (string) $request->input('number', '');
        $message = (string) $request->input('message', 'Teste de integração Connect|API - FERSOFT WEB.');

        if ($number === '') {
            return response()->json(['success' => false, 'error' => 'Informe o número de destino.'], 422);
        }

        return response()->json(
            $messages->sendText((int) $instance->empresa_id, $number, $message)
        );
    }

    public function sendWhatsAppButton(Request $request, ConnectApiClient $client, ConnectApiMessageService $messages)
    {
        $empresaId = (int) $this->empresa_id;
        if ($empresaId <= 0) {
            return response()->json(['success' => false, 'error' => 'Empresa do usuário não identificada.'], 422);
        }

        $number = (string) $request->input('number', '');
        $text = (string) $request->input('text', '');
        $caption = (string) $request->input('caption', '');
        $files = (array) $request->input('files', []);

        if ($number === '') {
            return response()->json(['success' => false, 'error' => 'Informe o número de destino.'], 422);
        }

        $instance = app(\App\Services\ConnectApi\ConnectApiIntegrationResolver::class)->forEmpresa($empresaId);
        $normalized = $messages->normalizeNumber($number);
        $responses = [];

        if ($text !== '') {
            $responses[] = $client->sendText($instance, $normalized, $text);
        }

        foreach ($files as $file) {
            $data = (string) ($file['data'] ?? '');
            $name = (string) ($file['name'] ?? 'arquivo');

            if ($data === '') {
                continue;
            }

            $mime = 'application/octet-stream';
            if (preg_match('/^data:([^;]+);base64,(.+)$/s', $data, $matches)) {
                $mime = $matches[1];
                $data = $matches[2];
            }

            $responses[] = $client->sendMedia(
                $instance,
                $normalized,
                $data,
                $name,
                $mime,
                $caption
            );
        }

        foreach ($responses as $response) {
            if (!($response['success'] ?? false)) {
                return response()->json($response, 502);
            }
        }

        return response()->json(['success' => true, 'responses' => $responses]);
    }

    private function owned(int $id, bool $superOnly = false): ConnectApiInstance
    {
        $user = session('user_logged', []);
        $isSuper = (bool) ($user['super'] ?? false);

        if ($superOnly && !$isSuper) {
            abort(403);
        }

        $query = ConnectApiInstance::query()->whereNull('deleted_at');

        if (!$isSuper) {
            $query->where('empresa_id', $this->empresa_id);
        }

        return $query->findOrFail($id);
    }

    private function requireSuper(): void
    {
        $user = session('user_logged', []);
        if (!($user['super'] ?? false)) {
            abort(403);
        }
    }

    private function present(ConnectApiInstance $instance): array
    {
        return [
            'id' => $instance->id,
            'empresa_id' => $instance->empresa_id,
            'instance_name' => $instance->instance_name,
            'connected_number' => $instance->connected_number,
            'connected_name' => $instance->connected_name,
            'connection_status' => $instance->connection_status,
            'is_blocked' => $instance->is_blocked,
            'last_event_at' => optional($instance->last_event_at)->toIso8601String(),
            'last_status_at' => optional($instance->last_status_at)->toIso8601String(),
            'last_error_message' => $instance->last_error_message,
        ];
    }
}
