<?php

namespace App\Services\ConnectApi;

use App\Models\ConnectApiInstance;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ConnectApiClient
{
    private function baseUrl(): string
    {
        $url = rtrim((string) config('connect_api.base_url'), '/');

        if ($url === '') {
            throw new \RuntimeException('CONNECT_API_BASE_URL não configurada.');
        }

        return $url;
    }

    private function request(?string $apiKey = null): PendingRequest
    {
        $key = $apiKey ?: (string) config('connect_api.bootstrap_key');

        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('connect_api.connect_timeout', 10))
            ->timeout((int) config('connect_api.timeout', 30))
            ->withHeaders(array_filter(['apikey' => $key]));
    }

    private function result(Response $response): array
    {
        $json = $response->json();

        if ($response->successful()) {
            return [
                'success' => true,
                'status' => $response->status(),
                'data' => is_array($json) ? $json : [],
            ];
        }

        return [
            'success' => false,
            'status' => $response->status(),
            'error' => $this->errorMessage($response, is_array($json) ? $json : []),
            'data' => is_array($json) ? $json : [],
        ];
    }

    public function createInstance(string $instanceName, string $token, string $number): array
    {
        return $this->result($this->request()->post('/instance/create', [
            'instanceName' => $instanceName,
            // Provider técnico de bootstrap exigido pelo contrato atual da Connect|API.
            // Não é uma escolha exposta ao usuário do ERP.
            'integration' => 'WHATSAPP-ZAPO',
            'token' => $token,
            'number' => preg_replace('/\\D+/', '', $number),
            'qrcode' => true,
            'groupsIgnore' => true,
            'alwaysOnline' => false,
            'readMessages' => false,
            'readStatus' => false,
            'syncFullHistory' => false,
        ]));
    }

    public function connectionState(ConnectApiInstance $instance): array
    {
        return $this->result(
            $this->request($instance->instance_token)
                ->get('/instance/connectionState/' . rawurlencode($instance->instance_name))
        );
    }

    public function connect(ConnectApiInstance $instance, ?string $number = null): array
    {
        $query = [];
        if ($number !== null && $number !== '') {
            $query['number'] = preg_replace('/\D+/', '', $number);
        }

        return $this->result(
            $this->request($instance->instance_token)
                ->get('/instance/connect/' . rawurlencode($instance->instance_name), $query)
        );
    }

    public function restart(ConnectApiInstance $instance): array
    {
        return $this->result(
            $this->request($instance->instance_token)
                ->post('/instance/restart/' . rawurlencode($instance->instance_name))
        );
    }

    public function logout(ConnectApiInstance $instance): array
    {
        return $this->result(
            $this->request($instance->instance_token)
                ->delete('/instance/logout/' . rawurlencode($instance->instance_name))
        );
    }

    public function delete(ConnectApiInstance $instance): array
    {
        return $this->result(
            $this->request($instance->instance_token)
                ->delete('/instance/delete/' . rawurlencode($instance->instance_name))
        );
    }

    public function configureWebhook(ConnectApiInstance $instance, string $url): array
    {
        return $this->result(
            $this->request($instance->instance_token)
                ->post('/webhook/set/' . rawurlencode($instance->instance_name), [
                    'webhook' => [
                        'enabled' => true,
                        'url' => $url,
                        'byEvents' => false,
                        'base64' => false,
                        'events' => config('connect_api.webhook_events', []),
                    ],
                ])
        );
    }

    public function sendText(ConnectApiInstance $instance, string $number, string $message): array
    {
        return $this->result(
            $this->request($instance->instance_token)
                ->post('/message/sendText/' . rawurlencode($instance->instance_name), [
                    'number' => $number,
                    'text' => $message,
                ])
        );
    }

    public function sendMedia(
        ConnectApiInstance $instance,
        string $number,
        string $media,
        string $fileName,
        string $mimeType,
        string $caption = ''
    ): array {
        return $this->result(
            $this->request($instance->instance_token)
                ->post('/message/sendMedia/' . rawurlencode($instance->instance_name), [
                    'number' => $number,
                    'mediaMessage' => [
                        'mediatype' => $this->mediaType($mimeType),
                        'fileName' => $fileName,
                        'mimetype' => $mimeType,
                        'caption' => $caption,
                        'media' => $media,
                    ],
                ])
        );
    }

    public function sendTemplate(
        ConnectApiInstance $instance,
        string $number,
        string $templateName,
        string $language,
        array $parameters = [],
        ?int $version = null
    ): array {
        $bodyParameters = array_map(
            fn ($value) => ['type' => 'text', 'text' => (string) $value],
            array_values($parameters)
        );

        $payload = [
            'number' => $number,
            'name' => $templateName,
            'language' => $language,
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => $bodyParameters,
                ],
            ],
        ];

        if ($version !== null) {
            $payload['version'] = $version;
        }

        return $this->result(
            $this->request($instance->instance_token)
                ->post('/message/sendTemplate/' . rawurlencode($instance->instance_name), $payload)
        );
    }

    public function createLocalTemplate(
        ConnectApiInstance $instance,
        string $name,
        string $language,
        string $body
    ): array {
        return $this->result(
            $this->request($instance->instance_token)
                ->post('/localTemplate/create/' . rawurlencode($instance->instance_name), [
                    'name' => $name,
                    'language' => $language,
                    'enabled' => true,
                    'components' => [
                        [
                            'type' => 'BODY',
                            'text' => $body,
                        ],
                    ],
                ])
        );
    }

    public function listLocalTemplates(ConnectApiInstance $instance): array
    {
        return $this->result(
            $this->request($instance->instance_token)
                ->get('/localTemplate/find/' . rawurlencode($instance->instance_name))
        );
    }

    private function errorMessage(Response $response, array $json): string
    {
        $candidates = [
            data_get($json, 'message'),
            data_get($json, 'response.message'),
            data_get($json, 'error.message'),
            data_get($json, 'error'),
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                $candidate = implode(' | ', array_map(
                    fn ($item) => is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE),
                    $candidate
                ));
            }

            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        $body = trim($response->body());

        return $body !== '' ? $body : 'Erro HTTP ' . $response->status() . ' na Connect|API.';
    }

    private function mediaType(string $mimeType): string
    {
        $prefix = strtolower(strtok($mimeType, '/'));

        return in_array($prefix, ['image', 'audio', 'video'], true)
            ? $prefix
            : 'document';
    }
}
