<?php

namespace App\Http\Controllers;

use App\Services\TraccarWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TraccarWebhookController extends Controller
{
    private TraccarWebhookService $webhookService;

    public function __construct(TraccarWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Entrada HTTP do Event Forwarding do Traccar.
     *
     * O processamento é idempotente e durável:
     * - cada evento relevante recebe uma dedupe_key UNIQUE no banco;
     * - duplicatas retornam 200 sem repetir a etapa;
     * - falhas transitórias ficam persistidas para retry;
     * - o frota:monitorar continua sendo o único escritor da máquina de
     *   estados da movimentação, preservando as regras existentes.
     */
    public function receberEvento(Request $request)
    {
        $validacao = $this->validarWebhook($request);

        if ($validacao !== true) {
            return $validacao;
        }

        try {
            $resultado = $this->webhookService->receber(
                $request->json()->all()
            );

            $httpStatus = (int) ($resultado['http_status'] ?? 200);
            unset($resultado['http_status']);

            return response()->json($resultado, $httpStatus);
        } catch (Throwable $e) {
            Log::error('Traccar webhook: falha não tratada na entrada HTTP.', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            // 503 faz o Traccar reconhecer falha transitória e permite
            // nova tentativa, enquanto o evento já persistido (quando possível)
            // continua disponível para o comando de reprocessamento.
            return response()->json([
                'status' => 'error',
                'message' => 'Falha transitória ao processar webhook Traccar.',
            ], 503);
        }
    }

    private function validarWebhook(Request $request)
    {
        $secret = (string) config('traccar.webhook.secret', '');

        if ($secret === '') {
            Log::error('Traccar webhook: TRACCAR_WEBHOOK_SECRET não configurado.');

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook Traccar não configurado no servidor.',
            ], 503);
        }

        $headerName = (string) config(
            'traccar.webhook.header',
            'X-Traccar-Webhook-Secret'
        );

        $recebido = (string) $request->header($headerName, '');

        if ($recebido === '' || !hash_equals($secret, $recebido)) {
            Log::warning('Traccar webhook: tentativa não autorizada.', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Não autorizado.',
            ], 401);
        }

        return true;
    }
}
