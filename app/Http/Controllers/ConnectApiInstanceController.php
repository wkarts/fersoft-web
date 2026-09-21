<?php

namespace App\Http\Controllers;

use App\Models\ConnectApiInstance;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Services\ConnectApi\ConnectApiClient;
use App\Services\ConnectApi\ConnectApiInstanceService;
use App\Services\ConnectApi\ConnectApiMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConnectApiInstanceController extends BaseController
{
    protected $redirectPage = '/connect-api';
    protected bool $isSuper = false;

    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request, $next) {
            $user = session('user_logged', []);
            $this->isSuper = (bool) ($user['super'] ?? false);

            return $next($request);
        });
    }

    public function index()
    {
        $companiesQuery = Empresa::query()
            ->withoutDeleted()
            ->select(['id', 'nome', 'nome_fantasia', 'cnpj', 'telefone', 'status'])
            ->orderBy('id');

        if (!$this->isSuper) {
            $companiesQuery->where('id', $this->empresa_id);
        }

        $companies = $companiesQuery->get();
        $companyIds = $companies->pluck('id');

        $instancesByCompany = ConnectApiInstance::query()
            ->whereNull('deleted_at')
            ->whereIn('empresa_id', $companyIds)
            ->get()
            ->keyBy('empresa_id');

        $isSuper = $this->isSuper;
        $title = 'Connect|API';

        $masterCompanyId = null;
        $masterLogin = trim((string) env('USERMASTER'));
        if ($masterLogin !== '') {
            $masterCompanyId = Usuario::query()
                ->where('login', $masterLogin)
                ->value('empresa_id');
        }

        return view('connect_api.index', compact(
            'companies',
            'instancesByCompany',
            'isSuper',
            'masterCompanyId',
            'title'
        ));
    }

    public function companies(Request $request)
    {
        $term = trim((string) $request->get('term', ''));

        $query = Empresa::query()
            ->withoutDeleted()
            ->when(!$this->isSuper, fn ($q) => $q->where('id', $this->empresa_id))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($sub) use ($term) {
                    $sub->where('nome', 'like', "%{$term}%")
                        ->orWhere('nome_fantasia', 'like', "%{$term}%")
                        ->orWhere('cnpj', 'like', "%{$term}%")
                        ->orWhere('telefone', 'like', "%{$term}%");
                });
            })
            ->orderBy('id')
            ->get(['id', 'nome', 'nome_fantasia', 'cnpj', 'telefone', 'status']);

        return response()->json($query);
    }

    public function provision(
        Request $request,
        int $empresa,
        ConnectApiInstanceService $service,
        ConnectApiMessageService $messages
    ) {
        $this->requireSuper();

        $number = $this->provisionNumber($request, $messages);
        if ($number instanceof \Illuminate\Http\JsonResponse) {
            return $number;
        }

        try {
            $model = Empresa::findOrFail($empresa);

            Log::info('Connect|API provisionamento solicitado.', [
                'empresa_id' => $model->id,
                'usuario_id' => $this->usuario_id,
                'telefone' => $this->maskNumber($number),
            ]);

            $instance = $service->provision(
                $model,
                $this->usuario_id,
                $this->filial_id,
                $number
            );

            Log::info('Connect|API provisionamento concluído.', [
                'empresa_id' => $model->id,
                'instance_id' => $instance->id,
                'instance_name' => $instance->instance_name,
                'status' => $instance->connection_status,
            ]);

            return response()->json([
                'success' => true,
                'instance' => $this->present($instance),
                'message' => 'Instância provisionada. Continue o pareamento pelo código ou QR Code.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao provisionar Connect|API.', [
                'empresa_id' => $empresa,
                'usuario_id' => $this->usuario_id,
                'telefone' => $this->maskNumber($number),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    public function reprovision(
        int $id,
        Request $request,
        ConnectApiInstanceService $service,
        ConnectApiMessageService $messages
    ) {
        $this->requireSuper();

        $number = $this->provisionNumber($request, $messages);
        if ($number instanceof \Illuminate\Http\JsonResponse) {
            return $number;
        }

        try {
            $instance = $this->owned($id, true);

            Log::warning('Connect|API reprovisionamento solicitado.', [
                'instance_id' => $instance->id,
                'empresa_id' => $instance->empresa_id,
                'instance_name' => $instance->instance_name,
                'usuario_id' => $this->usuario_id,
                'telefone' => $this->maskNumber($number),
            ]);

            $instance = $service->reprovision($instance, $this->usuario_id, $number);

            return response()->json([
                'success' => true,
                'instance' => $this->present($instance),
                'message' => 'Instância reprovisionada. Será necessário concluir o novo pareamento.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao reprovisionar Connect|API.', [
                'instance_id' => $id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    public function deleteInstance(int $id, ConnectApiInstanceService $service)
    {
        $this->requireSuper();

        try {
            $instance = $this->owned($id, true);
            $empresaId = (int) $instance->empresa_id;
            $instanceName = $instance->instance_name;

            $service->deleteInstance($instance, $this->usuario_id);

            Log::warning('Instância Connect|API excluída pelo Master.', [
                'instance_id' => $id,
                'empresa_id' => $empresaId,
                'instance_name' => $instanceName,
                'usuario_id' => $this->usuario_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Instância excluída local e remotamente.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao excluir instância Connect|API.', [
                'instance_id' => $id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    public function syncWebhook(int $id, ConnectApiInstanceService $service)
    {
        $this->requireSuper();

        try {
            $instance = $this->owned($id, true);
            $instance = $service->syncWebhook($instance);

            return response()->json([
                'success' => true,
                'instance' => $this->present($instance),
                'message' => 'Webhook sincronizado automaticamente com a URL pública desta instalação.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao sincronizar webhook Connect|API.', [
                'instance_id' => $id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    public function status(int $id, ConnectApiInstanceService $service)
    {
        try {
            $instance = $this->owned($id);
            $instance = $service->refreshStatus($instance);

            return response()->json([
                'success' => true,
                'instance' => $this->present($instance),
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao consultar status Connect|API.', [
                'instance_id' => $id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function qr(int $id, ConnectApiInstanceService $service)
    {
        try {
            $instance = $this->owned($id);
            $response = $service->pairing($instance);

            return response()->json($response, ($response['success'] ?? false) ? 200 : 502);
        } catch (\Throwable $e) {
            Log::error('Falha ao gerar QR Connect|API.', [
                'instance_id' => $id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function pairingCode(
        int $id,
        Request $request,
        ConnectApiInstanceService $service,
        ConnectApiMessageService $messages
    ) {
        $instance = $this->owned($id);
        $number = $messages->normalizeNumber((string) $request->input('number', ''));

        if ($number === '') {
            return response()->json(['success' => false, 'error' => 'Informe o número do WhatsApp.'], 422);
        }

        try {
            $response = $service->pairing($instance, $number);

            return response()->json($response, ($response['success'] ?? false) ? 200 : 502);
        } catch (\Throwable $e) {
            Log::error('Falha ao gerar código de pareamento Connect|API.', [
                'instance_id' => $id,
                'empresa_id' => $instance->empresa_id,
                'telefone' => $this->maskNumber($number),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function restart(
        int $id,
        ConnectApiClient $client,
        ConnectApiInstanceService $service
    ) {
        try {
            $instance = $this->owned($id);

            if (!$instance->webhook_configured_at && $instance->provisioned_at) {
                $instance = $service->syncWebhook($instance);
            }

            $response = $client->restart($instance);

            return response()->json($response, ($response['success'] ?? false) ? 200 : 502);
        } catch (\Throwable $e) {
            Log::error('Falha ao reiniciar Connect|API.', [
                'instance_id' => $id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function disconnect(int $id, ConnectApiClient $client)
    {
        try {
            $instance = $this->owned($id);
            $response = $client->logout($instance);

            if ($response['success'] ?? false) {
                $instance->connection_status = 'close';
                $instance->disconnected_at = now();
                $instance->save();
            }

            return response()->json($response, ($response['success'] ?? false) ? 200 : 502);
        } catch (\Throwable $e) {
            Log::error('Falha ao desconectar Connect|API.', [
                'instance_id' => $id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function block(int $id, ConnectApiInstanceService $service)
    {
        $this->requireSuper();

        try {
            $instance = $this->owned($id, true);
            $instance = $service->blockInstance($instance, $this->usuario_id);

            return response()->json([
                'success' => true,
                'instance' => $this->present($instance),
                'message' => 'Instância bloqueada e desconectada.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao bloquear Connect|API.', [
                'instance_id' => $id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function unblock(int $id)
    {
        $this->requireSuper();

        $instance = $this->owned($id, true);
        $instance->is_blocked = false;
        $instance->updated_by = $this->usuario_id;
        $instance->save();

        Log::info('Instância Connect|API desbloqueada.', [
            'instance_id' => $id,
            'empresa_id' => $instance->empresa_id,
            'usuario_id' => $this->usuario_id,
        ]);

        return response()->json([
            'success' => true,
            'instance' => $this->present($instance),
            'message' => 'Instância desbloqueada.',
        ]);
    }

    public function testMessage(int $id, Request $request, ConnectApiMessageService $messages)
    {
        $instance = $this->owned($id);
        $number = (string) $request->input('number', '');
        $message = (string) $request->input('message', 'Teste de integração Connect|API - FERSOFT WEB.');

        if ($number === '') {
            return response()->json(['success' => false, 'error' => 'Informe o número de destino.'], 422);
        }

        try {
            $response = $messages->sendText((int) $instance->empresa_id, $number, $message);

            return response()->json($response, ($response['success'] ?? false) ? 200 : 502);
        } catch (\Throwable $e) {
            Log::error('Falha no teste de mensagem Connect|API.', [
                'instance_id' => $id,
                'empresa_id' => $instance->empresa_id,
                'telefone' => $this->maskNumber($number),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function sendWhatsAppButton(
        Request $request,
        ConnectApiClient $client,
        ConnectApiMessageService $messages,
        ConnectApiInstanceService $service
    )
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

        try {
            $instance = app(\App\Services\ConnectApi\ConnectApiIntegrationResolver::class)->forEmpresa($empresaId);

            if (!$instance->webhook_configured_at && $instance->provisioned_at) {
                $instance = $service->syncWebhook($instance);
            }

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
        } catch (\Throwable $e) {
            Log::error('Falha no botão global Connect|API.', [
                'empresa_id' => $empresaId,
                'usuario_id' => $this->usuario_id,
                'telefone' => $this->maskNumber($number),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 502);
        }
    }

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    private function provisionNumber(Request $request, ConnectApiMessageService $messages)
    {
        $raw = trim((string) $request->input('number', ''));

        if ($raw === '') {
            return response()->json([
                'success' => false,
                'error' => 'Informe o número do WhatsApp para provisionar a instância.',
            ], 422);
        }

        $number = $messages->normalizeNumber($raw);

        if (strlen($number) < 8 || strlen($number) > 15) {
            return response()->json([
                'success' => false,
                'error' => 'Número inválido. Informe DDI, DDD e número do WhatsApp.',
            ], 422);
        }

        return $number;
    }

    private function owned(int $id, bool $superOnly = false): ConnectApiInstance
    {
        if ($superOnly && !$this->isSuper) {
            abort(403);
        }

        $query = ConnectApiInstance::query()->whereNull('deleted_at');

        if (!$this->isSuper) {
            $query->where('empresa_id', $this->empresa_id);
        }

        return $query->findOrFail($id);
    }

    private function requireSuper(): void
    {
        if (!$this->isSuper) {
            abort(403);
        }
    }

    private function maskNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);
        if ($digits === '') {
            return '';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
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
            'webhook_configured_at' => optional($instance->webhook_configured_at)->toIso8601String(),
            'webhook_last_received_at' => optional($instance->webhook_last_received_at)->toIso8601String(),
        ];
    }
}
