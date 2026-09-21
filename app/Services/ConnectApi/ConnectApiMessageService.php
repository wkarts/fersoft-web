<?php

namespace App\Services\ConnectApi;

use App\Models\ConnectApiInstance;
use App\Models\ConnectApiTemplateBinding;
use Illuminate\Support\Str;

class ConnectApiMessageService
{
    public function __construct(
        private readonly ConnectApiClient $client,
        private readonly ConnectApiIntegrationResolver $resolver,
        private readonly ConnectApiInstanceService $instances
    ) {
    }

    public function sendText(int $empresaId, string $number, string $message): array
    {
        $instance = $this->readyInstance($empresaId);

        return $this->client->sendText(
            $instance,
            $this->normalizeNumber($number),
            $message
        );
    }

    public function sendMedia(
        int $empresaId,
        string $number,
        string $file,
        string $caption = ''
    ): array {
        $instance = $this->readyInstance($empresaId);
        [$media, $name, $mime] = $this->prepareMedia($file);

        return $this->client->sendMedia(
            $instance,
            $this->normalizeNumber($number),
            $media,
            $name,
            $mime,
            $caption
        );
    }

    public function sendTemplate(
        int $empresaId,
        string $number,
        string $eventKey,
        array $parameters = []
    ): array {
        $instance = $this->readyInstance($empresaId);

        $binding = ConnectApiTemplateBinding::query()
            ->where('enabled', true)
            ->where('event_key', $eventKey)
            ->where(function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId)->orWhereNull('empresa_id');
            })
            ->orderByRaw('CASE WHEN empresa_id = ? THEN 0 ELSE 1 END', [$empresaId])
            ->first();

        if (!$binding) {
            throw new \RuntimeException("Template não configurado para o evento {$eventKey}.");
        }

        return $this->client->sendTemplate(
            $instance,
            $this->normalizeNumber($number),
            $binding->template_name,
            $binding->language ?: 'pt_BR',
            $parameters,
            $binding->template_version
        );
    }

    public function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);
        $ddi = (string) config('connect_api.ddi', '55');
        $ddd = (string) config('connect_api.ddd', '75');

        if (str_starts_with($digits, $ddi)) {
            return $digits;
        }

        if (strlen($digits) === 8 || strlen($digits) === 9) {
            return $ddi . $ddd . $digits;
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return $ddi . $digits;
        }

        return $digits;
    }

    private function readyInstance(int $empresaId): ConnectApiInstance
    {
        $instance = $this->resolver->forEmpresa($empresaId);

        if (!$instance->provisioned_at || !$instance->instance_token) {
            throw new \RuntimeException(
                'A instância WhatsApp ainda não foi provisionada na Connect|API.'
            );
        }

        // O webhook mantém o estado normalmente atualizado. Quando a instância
        // não está marcada como aberta, fazemos uma consulta pontual antes de
        // recusar o envio para não depender de um status local eventualmente atrasado.
        if ($instance->connection_status !== 'open') {
            $instance = $this->instances->refreshStatus($instance);
        }

        if ($instance->connection_status !== 'open') {
            throw new \RuntimeException(
                'A instância WhatsApp não está conectada na Connect|API. Status atual: '
                . ($instance->connection_status ?: 'unknown')
                . '.'
            );
        }

        if (!$instance->webhook_configured_at) {
            $instance = $this->instances->syncWebhook($instance);
        }

        return $instance;
    }

    private function prepareMedia(string $file): array
    {
        if (Str::startsWith($file, ['http://', 'https://'])) {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($file);
            if (!$response->successful()) {
                throw new \RuntimeException('Não foi possível baixar a mídia informada.');
            }

            $content = $response->body();
            $name = basename((string) parse_url($file, PHP_URL_PATH)) ?: 'arquivo';
            $mime = $response->header('Content-Type') ?: 'application/octet-stream';

            return [base64_encode($content), $name, $mime];
        }

        if (!is_file($file)) {
            throw new \RuntimeException("Arquivo não encontrado: {$file}");
        }

        $content = safe_file_get_contents($file);
        if ($content === false) {
            throw new \RuntimeException("Não foi possível ler o arquivo: {$file}");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file) ?: 'application/octet-stream';
        finfo_close($finfo);

        return [base64_encode($content), basename($file), $mime];
    }
}
