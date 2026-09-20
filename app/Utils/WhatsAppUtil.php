<?php

namespace App\Utils;

use App\Services\ConnectApi\ConnectApiMessageService;
use Illuminate\Support\Facades\Log;

class WhatsAppUtil
{
    public function __construct(
        protected ConnectApiMessageService $connectApi
    ) {
    }

    /**
     * Fachada de compatibilidade do ERP.
     *
     * A assinatura é preservada para não exigir refatoração abrupta dos módulos
     * existentes. Todo envio passa exclusivamente pela Connect|API.
     */
    public function sendMessage(
        string $numero,
        ?string $mensagem,
        int $empresa_id,
        ?string $file = null
    ): string {
        set_time_limit(120);

        try {
            $textResponse = null;
            $mediaResponse = null;

            if (!empty($mensagem)) {
                $textResponse = $this->connectApi->sendText(
                    $empresa_id,
                    $numero,
                    $mensagem
                );

                if (!($textResponse['success'] ?? false)) {
                    return $this->encode(false, $textResponse, null);
                }
            }

            if ($file !== null && $file !== '') {
                $mediaResponse = $this->connectApi->sendMedia(
                    $empresa_id,
                    $numero,
                    $file,
                    ''
                );

                if (!($mediaResponse['success'] ?? false)) {
                    return $this->encode(false, $textResponse, $mediaResponse);
                }
            }

            return $this->encode(true, $textResponse, $mediaResponse);
        } catch (\Throwable $e) {
            Log::error('WhatsAppUtil::sendMessage falhou via Connect|API', [
                'numero' => $numero,
                'empresa_id' => $empresa_id,
                'erro' => $e->getMessage(),
            ]);

            return json_encode([
                'success' => false,
                'text_response' => null,
                'media_response' => null,
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function sendTemplate(
        string $numero,
        string $eventKey,
        int $empresa_id,
        array $parameters = []
    ): string {
        try {
            $response = $this->connectApi->sendTemplate(
                $empresa_id,
                $numero,
                $eventKey,
                $parameters
            );

            return json_encode([
                'success' => (bool) ($response['success'] ?? false),
                'response' => $response,
                'message' => ($response['success'] ?? false)
                    ? 'Template enviado com sucesso.'
                    : ($response['error'] ?? 'Falha ao enviar template.'),
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Log::error('WhatsAppUtil::sendTemplate falhou via Connect|API', [
                'numero' => $numero,
                'empresa_id' => $empresa_id,
                'event_key' => $eventKey,
                'erro' => $e->getMessage(),
            ]);

            return json_encode([
                'success' => false,
                'response' => null,
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function encode(bool $success, ?array $text, ?array $media): string
    {
        $error = $text['error'] ?? $media['error'] ?? null;

        return json_encode([
            'success' => $success,
            'text_response' => $text,
            'media_response' => $media,
            'message' => $success
                ? 'Mensagem enviada com sucesso.'
                : ($error ?: 'Falha ao enviar mensagem.'),
        ], JSON_UNESCAPED_UNICODE);
    }
}
