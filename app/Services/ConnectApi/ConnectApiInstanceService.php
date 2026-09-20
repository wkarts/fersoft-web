<?php

namespace App\Services\ConnectApi;

use App\Models\ConnectApiInstance;
use App\Models\Empresa;
use App\Models\ConnectApiTemplateBinding;
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

        if ($instance && $instance->remote_instance_id) {
            return $instance;
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

        $webhookUrl = $this->webhookUrl();
        if ($webhookUrl !== '') {
            $this->client->configureWebhook($instance, $webhookUrl);
        }

        $this->syncDefaultTemplates($instance);

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

        return $instance->fresh();
    }

    public function pairing(ConnectApiInstance $instance, ?string $number = null): array
    {
        $response = $this->client->connect($instance, $number);

        if ($response['success'] ?? false) {
            $instance->connection_status = 'awaiting_pairing';
            $instance->last_status_at = now();
            $instance->save();
        }

        return $response;
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

            // Conflito/registro existente é aceitável: o binding continua válido.
            if (($response['success'] ?? false) || in_array((int) ($response['status'] ?? 0), [400, 409, 422], true)) {
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

    private function webhookUrl(): string
    {
        $configured = trim((string) config('connect_api.webhook_url'));
        $base = $configured !== ''
            ? $configured
            : rtrim((string) config('app.url'), '/') . '/api/webhooks/connect-api';

        if ($base === '' || $base === '/api/webhooks/connect-api') {
            return '';
        }

        $secret = (string) config('connect_api.webhook_secret');

        if ($secret === '') {
            return $base;
        }

        return $base
            . (str_contains($base, '?') ? '&' : '?')
            . 'secret=' . rawurlencode($secret);
    }
}
