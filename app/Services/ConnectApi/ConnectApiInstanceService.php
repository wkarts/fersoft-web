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

    public function provision(Empresa $empresa, ?int $usuarioId = null, ?int $filialId = null): ConnectApiInstance
    {
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

        $response = $this->client->createInstance($instance->instance_name, $instance->instance_token);

        if (!($response['success'] ?? false)) {
            $instance->connection_status = 'error';
            $instance->last_error_code = (string) ($response['status'] ?? '');
            $instance->last_error_message = (string) ($response['error'] ?? 'Falha ao provisionar Connect|API.');
            $instance->last_error_at = now();
            $instance->save();

            throw new \RuntimeException($instance->last_error_message);
        }

        $data = $response['data'] ?? [];
        $instance->remote_instance_id = data_get($data, 'instance.instanceId')
            ?: data_get($data, 'instance.instance_id')
            ?: data_get($data, 'instance.id');

        $returnedToken = data_get($data, 'hash.apikey') ?: data_get($data, 'apikey');
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

        return $instance->fresh();
    }

    public function reprovision(ConnectApiInstance $instance, ?int $usuarioId = null): ConnectApiInstance
    {
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
            $instance->filial_id
        );
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

        $response = $this->client->configureWebhook($instance, $webhookUrl);

        if (!($response['success'] ?? false)) {
            $instance->webhook_configured_at = null;
            $instance->last_error_code = 'webhook:' . (string) ($response['status'] ?? '');
            $instance->last_error_message = (string) ($response['error'] ?? 'Falha ao configurar webhook na Connect|API.');
            $instance->last_error_at = now();
            $instance->save();

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
