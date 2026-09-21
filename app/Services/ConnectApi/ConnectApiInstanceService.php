<?php

namespace App\Services\ConnectApi;

use App\Models\ConnectApiInstance;
use App\Models\ConnectApiTemplateBinding;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConnectApiInstanceService
{
    public function __construct(private readonly ConnectApiClient $client)
    {
    }

    public function generateInstanceName(Empresa $empresa): string
    {
        $cnpj = preg_replace('/\D+/', '', (string) $empresa->cnpj);
        $razao = Str::of((string) $empresa->nome)
            ->ascii()
            ->upper()
            ->replaceMatches('/\W+/', '_')
            ->trim('_')
            ->toString();

        $prefix = trim((string) config('connect_api.instance_prefix'));

        $base = trim(($prefix !== '' ? $prefix . '-' : '') . $cnpj . '-' . $razao, '-');

        return Str::limit($base, 100, '');
    }

    public function provision(
        Empresa $empresa,
        ?int $usuarioId = null,
        ?int $filialId = null,
        string $number = ''
    ): ConnectApiInstance
    {
        $number = preg_replace('/\\D+/', '', $number);

        if (strlen($number) < 8 || strlen($number) > 15) {
            throw new \InvalidArgumentException(
                'Informe um número de WhatsApp válido com DDI, DDD e número.'
            );
        }

        Log::info('Connect|API: início do provisionamento.', [
            'empresa_id' => $empresa->id,
            'usuario_id' => $usuarioId,
            'telefone' => $this->maskNumber($number),
        ]);

        $instance = ConnectApiInstance::query()
            ->where('empresa_id', $empresa->id)
            ->whereNull('deleted_at')
            ->first();

        if ($instance && $instance->provisioned_at && $instance->instance_token) {
            return $this->syncWebhook($instance);
        }

        $instance ??= new ConnectApiInstance();
        $instance->empresa_id = $empresa->id;
        $instance->usuario_id = $usuarioId;
        $instance->filial_id = ($filialId && $filialId > 0) ? $filialId : null;
        $instance->instance_name = $instance->instance_name ?: $this->generateInstanceName($empresa);
        $instance->instance_token = Str::random(64);
        $instance->connection_status = 'provisioning';
        $instance->created_by = $instance->created_by ?: $usuarioId;
        $instance->updated_by = $usuarioId;
        $instance->save();

        $response = $this->client->createInstance(
            $instance->instance_name,
            $instance->instance_token,
            $number
        );

        Log::info('Connect|API: resposta de criação recebida.', [
            'empresa_id' => $empresa->id,
            'instance_name' => $instance->instance_name,
            'http_status' => $response['status'] ?? null,
            'success' => (bool) ($response['success'] ?? false),
        ]);

        if (!($response['success'] ?? false)) {
            $instance->connection_status = 'error';
            $instance->last_error_code = (string) ($response['status'] ?? '');
            $remoteError = (string) ($response['error'] ?? 'Falha ao provisionar Connect|API.');
            $instance->last_error_message = 'Connect|API (HTTP '
                . (string) ($response['status'] ?? 'erro')
                . '): '
                . $remoteError;
            $instance->last_error_at = now();
            $instance->save();

            Log::error('Connect|API: falha ao criar instância remota.', [
                'empresa_id' => $empresa->id,
                'instance_name' => $instance->instance_name,
                'http_status' => $response['status'] ?? null,
                'error' => $remoteError,
            ]);

            throw new \RuntimeException($instance->last_error_message);
        }

        $data = $response['data'] ?? [];
        $instance->remote_instance_id = data_get($data, 'instance.instanceId')
            ?: data_get($data, 'instance.instance_id')
            ?: data_get($data, 'instance.id');

        $returnedToken = data_get($data, 'hash.apikey')
            ?: data_get($data, 'hash')
            ?: data_get($data, 'apikey');
        if ($returnedToken) {
            $instance->instance_token = $returnedToken;
        }

        $instance->connection_status = data_get($data, 'instance.status', 'awaiting_pairing');
        $instance->provisioned_at = now();
        $instance->last_error_code = null;
        $instance->last_error_message = null;
        $instance->last_error_at = null;
        $instance->save();

        $instance = $this->syncWebhook($instance);
        $this->syncDefaultTemplates($instance);

        Log::info('Connect|API: provisionamento concluído.', [
            'empresa_id' => $empresa->id,
            'instance_id' => $instance->id,
            'instance_name' => $instance->instance_name,
            'remote_instance_id' => $instance->remote_instance_id,
            'status' => $instance->connection_status,
        ]);

        return $instance->fresh();
    }

    public function reprovision(
        ConnectApiInstance $instance,
        ?int $usuarioId = null,
        string $number = ''
    ): ConnectApiInstance
    {
        if ($instance->provisioned_at && $instance->instance_token) {
            $deleteResponse = $this->client->delete($instance);

            if (
                !($deleteResponse['success'] ?? false)
                && (int) ($deleteResponse['status'] ?? 0) !== 404
            ) {
                throw new \RuntimeException(
                    (string) ($deleteResponse['error'] ?? 'Falha ao remover a instância remota antes do reprovisionamento.')
                );
            }

            Log::warning('Connect|API: instância remota removida para reprovisionamento.', [
                'instance_id' => $instance->id,
                'empresa_id' => $instance->empresa_id,
                'instance_name' => $instance->instance_name,
                'usuario_id' => $usuarioId,
                'remote_status' => $deleteResponse['status'] ?? null,
            ]);
        }

        $instance->remote_instance_id = null;
        $instance->instance_token = null;
        $instance->webhook_token = null;
        $instance->webhook_token_hash = null;
        $instance->webhook_configured_at = null;
        $instance->webhook_last_received_at = null;
        $instance->provisioned_at = null;
        $instance->connection_status = 'awaiting_provisioning';
        $instance->connected_number = null;
        $instance->connected_name = null;
        $instance->paired_at = null;
        $instance->connected_at = null;
        $instance->disconnected_at = null;
        $instance->last_error_code = null;
        $instance->last_error_message = null;
        $instance->last_error_at = null;
        $instance->updated_by = $usuarioId;
        $instance->save();

        return $this->provision(
            $instance->empresa,
            $usuarioId,
            $instance->filial_id,
            $number
        );
    }

    public function deleteInstance(ConnectApiInstance $instance, ?int $usuarioId = null): void
    {
        $remoteResponse = null;

        if ($instance->provisioned_at && $instance->instance_token) {
            $remoteResponse = $this->client->delete($instance);

            if (
                !($remoteResponse['success'] ?? false)
                && (int) ($remoteResponse['status'] ?? 0) !== 404
            ) {
                throw new \RuntimeException(
                    (string) ($remoteResponse['error'] ?? 'Falha ao excluir instância na Connect|API.')
                );
            }
        }

        Log::warning('Connect|API: exclusão de instância.', [
            'instance_id' => $instance->id,
            'empresa_id' => $instance->empresa_id,
            'instance_name' => $instance->instance_name,
            'usuario_id' => $usuarioId,
            'remote_status' => $remoteResponse['status'] ?? null,
        ]);

        $instance->updated_by = $usuarioId;
        $instance->delete();
    }

    public function blockInstance(
        ConnectApiInstance $instance,
        ?int $usuarioId = null
    ): ConnectApiInstance {
        if ($instance->provisioned_at && $instance->instance_token) {
            $response = $this->client->logout($instance);

            if (
                !($response['success'] ?? false)
                && !in_array((int) ($response['status'] ?? 0), [404, 409], true)
            ) {
                throw new \RuntimeException(
                    (string) ($response['error'] ?? 'Falha ao desconectar instância antes do bloqueio.')
                );
            }
        }

        $instance->is_blocked = true;
        $instance->connection_status = 'blocked';
        $instance->disconnected_at = now();
        $instance->updated_by = $usuarioId;
        $instance->save();

        Log::warning('Connect|API: instância bloqueada.', [
            'instance_id' => $instance->id,
            'empresa_id' => $instance->empresa_id,
            'instance_name' => $instance->instance_name,
            'usuario_id' => $usuarioId,
        ]);

        return $instance->fresh();
    }

    public function refreshStatus(ConnectApiInstance $instance): ConnectApiInstance
    {
        $response = $this->client->connectionState($instance);

        if ($response['success'] ?? false) {
            $state = data_get($response, 'data.instance.state')
                ?: data_get($response, 'data.instance.status')
                ?: data_get($response, 'data.state')
                ?: 'unknown';

            $instance->connection_status = $state;
            $instance->last_status_at = now();

            if ($state === 'open') {
                $instance->connected_at = $instance->connected_at ?: now();
                $instance->disconnected_at = null;
            } elseif ($state === 'close') {
                $instance->disconnected_at = now();
            }

            $instance->save();
        } else {
            $instance->connection_status = 'not_found';
            $instance->last_error_code = (string) ($response['status'] ?? '');
            $instance->last_error_message = (string) ($response['error'] ?? 'Instância não encontrada na Connect|API.');
            $instance->last_error_at = now();
            $instance->last_status_at = now();
            $instance->save();
        }

        if (!$instance->webhook_configured_at && $instance->provisioned_at && $instance->instance_token) {
            try {
                $instance = $this->syncWebhook($instance->fresh());
            } catch (\Throwable $e) {
                Log::warning('Falha ao sincronizar webhook Connect|API durante consulta de status.', [
                    'instance_id' => $instance->id,
                    'empresa_id' => $instance->empresa_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $instance->fresh();
    }

    public function pairing(ConnectApiInstance $instance, ?string $number = null): array
    {
        if (!$instance->webhook_configured_at) {
            $instance = $this->syncWebhook($instance);
        }

        $response = $this->client->connect($instance, $number);

        if ($response['success'] ?? false) {
            $instance->connection_status = 'awaiting_pairing';
            $instance->last_status_at = now();
            $instance->save();
        }

        return $response;
    }

    public function syncWebhook(ConnectApiInstance $instance, bool $rotateToken = false): ConnectApiInstance
    {
        if (!$instance->provisioned_at || !$instance->instance_token) {
            throw new \RuntimeException('A instância Connect|API ainda não foi provisionada.');
        }

        $token = $this->ensureWebhookToken($instance, $rotateToken);
        $webhookUrl = $this->buildWebhookUrl($token);

        Log::info('Connect|API: sincronizando webhook.', [
            'instance_id' => $instance->id,
            'empresa_id' => $instance->empresa_id,
            'instance_name' => $instance->instance_name,
            'webhook_host' => parse_url($webhookUrl, PHP_URL_HOST),
        ]);

        $response = $this->client->configureWebhook($instance, $webhookUrl);

        if (!($response['success'] ?? false)) {
            $instance->webhook_configured_at = null;
            $instance->last_error_code = 'webhook:' . (string) ($response['status'] ?? '');
            $instance->last_error_message = (string) ($response['error'] ?? 'Falha ao configurar webhook na Connect|API.');
            $instance->last_error_at = now();
            $instance->save();

            Log::error('Connect|API: falha na sincronização do webhook.', [
                'instance_id' => $instance->id,
                'empresa_id' => $instance->empresa_id,
                'instance_name' => $instance->instance_name,
                'http_status' => $response['status'] ?? null,
                'error' => $instance->last_error_message,
            ]);

            throw new \RuntimeException($instance->last_error_message);
        }

        $instance->webhook_configured_at = now();

        if (str_starts_with((string) $instance->last_error_code, 'webhook:')) {
            $instance->last_error_code = null;
            $instance->last_error_message = null;
            $instance->last_error_at = null;
        }

        $instance->save();

        return $instance->fresh();
    }

    public function detectPublicBaseUrl(): string
    {
        $candidates = [];

        try {
            if (app()->bound('request')) {
                $request = request();
                if ($request && $request->headers->has('host') && $request->getHost()) {
                    $candidates[] = $request->getSchemeAndHttpHost();
                }
            }
        } catch (\Throwable $e) {
            // Sem contexto HTTP (CLI/queue): usa APP_URL como fallback.
        }

        $candidates[] = (string) config('app.url');

        foreach ($candidates as $candidate) {
            $candidate = rtrim(trim((string) $candidate), '/');
            if ($candidate === '') {
                continue;
            }

            $parts = parse_url($candidate);
            $scheme = strtolower((string) ($parts['scheme'] ?? ''));
            $host = (string) ($parts['host'] ?? '');

            if (in_array($scheme, ['http', 'https'], true) && $host !== '') {
                $port = isset($parts['port']) ? ':' . $parts['port'] : '';
                return $scheme . '://' . $host . $port;
            }
        }

        throw new \RuntimeException('Não foi possível detectar automaticamente a URL pública desta instalação FERSOFT WEB.');
    }

    public function buildWebhookUrl(string $token): string
    {
        return $this->detectPublicBaseUrl()
            . '/api/webhooks/connect-api/'
            . rawurlencode($token);
    }

    public function syncDefaultTemplates(ConnectApiInstance $instance): void
    {
        foreach ((array) config('connect_api.default_templates', []) as $name => $definition) {
            $response = $this->client->createLocalTemplate(
                $instance,
                (string) $name,
                (string) ($definition['language'] ?? 'pt_BR'),
                (string) ($definition['body'] ?? '')
            );

            if (($response['success'] ?? false) || (int) ($response['status'] ?? 0) === 409) {
                ConnectApiTemplateBinding::firstOrCreate(
                    [
                        'empresa_id' => $instance->empresa_id,
                        'event_key' => (string) ($definition['event_key'] ?? $name),
                    ],
                    [
                        'template_name' => (string) $name,
                        'language' => (string) ($definition['language'] ?? 'pt_BR'),
                        'enabled' => true,
                    ]
                );
            }
        }
    }

    private function maskNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);

        return $digits === ''
            ? ''
            : str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    private function ensureWebhookToken(ConnectApiInstance $instance, bool $rotateToken): string
    {
        $token = $rotateToken ? null : $instance->webhook_token;

        if (!$token) {
            $token = Str::random(64);
            $instance->webhook_token = $token;
        }

        $hash = hash('sha256', $token);

        if ($instance->webhook_token_hash !== $hash) {
            $instance->webhook_token_hash = $hash;
        }

        $instance->save();

        return $token;
    }
}
