<?php

namespace App\Utils;

use Illuminate\Support\Str;
use App\Models\ConfigNota;
use App\Models\EvoApiInstance;
use App\Services\EvoApiService;

class WhatsAppUtil
{
    protected EvoApiService $evoService;

    public function __construct(EvoApiService $evoService)
    {
        $this->evoService = $evoService;
    }

    /**
     * Envia mensagem de texto e/ou arquivo via WhatsApp,
     * escolhendo a tecnologia em ConfigNota->whatsapp_technology.
     *
     * @param string      $numero     E.164 (ex: "5511999998888")
     * @param string|null $mensagem   Texto a ser enviado
     * @param int         $empresa_id ID do tenant
     * @param string|null $file       Caminho absoluto ou URL do arquivo
     * @return string    JSON com { success, text_response, media_response, message }
     */
    public function sendMessage(string $numero, ?string $mensagem, int $empresa_id, ?string $file = null): string
    {
        set_time_limit(120);

        try {
            $configNota = ConfigNota::where('empresa_id', $empresa_id)->first();
            if (! $configNota) {
                throw new \Exception("Configuração de WhatsApp não encontrada para empresa_id: {$empresa_id}");
            }

            // ──────────────── ➤ BLOQUEIO
            $inst = EvoApiInstance::where('empresa_id', $empresa_id)->first();
            if ($inst && $inst->is_blocked) {
                return json_encode([
                    'success'        => false,
                    'text_response'  => null,
                    'media_response' => null,
                    'message'        => 'Esta instância está bloqueada. Entre em contato com o suporte para desbloquear.'
                ]);
            }

            if ($configNota->whatsapp_technology === 'evo') {
                return $this->sendWithEvo($numero, $mensagem, $file, $empresa_id);
            }

            return $this->sendWithLegacy($numero, $mensagem, $configNota->token_whatsapp, $file, $empresa_id);

        } catch (\Exception $e) {
            \Log::error("WhatsAppUtil::sendMessage falhou", [
                'numero'     => $numero,
                'empresa_id' => $empresa_id,
                'erro'       => $e->getMessage(),
            ]);
            return json_encode([
                'success'        => false,
                'text_response'  => null,
                'media_response' => null,
                'message'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envio legado via cURL
     */
    protected function sendWithLegacy(
        string $numero,
        ?string $mensagem,
        string $token,
        ?string $file,
        int $empresa_id
    ): string {
        $tokenPrefix = env('TOKEN_PREFIX', '');
        if (Str::startsWith($token, $tokenPrefix . ':')) {
            $token = Str::after($token, $tokenPrefix . ':');
            return $this->sendWithEvo($numero, $mensagem, $file, $empresa_id);
        }

        $apiEndpoint = env(
            'API_WHATSAPP_ATENDIMENTO',
            'https://api.atendimento.wwsoftwares.com.br/api/messages/send'
        );

        $numero = $this->formatPhoneNumber($numero);

        $textResponse  = null;
        $mediaResponse = null;

        if (! empty($mensagem)) {
            $textResponse = $this->sendTextMessage($numero, $mensagem, $token, $apiEndpoint);
        }

        if ($file !== null) {
            if (env('FORCE_PUBLIC_PATH', false)) {
                $file = $this->forcePublicPath($file);
            }
            $mediaResponse = $this->sendMediaMessage($numero, $file, $token, $apiEndpoint);
        }

        $textParsed  = $this->processResponse($textResponse);
        $mediaParsed = $file ? $this->processResponse($mediaResponse) : null;

        $message = $textParsed['mensagem_sucesso']
            ?? $textParsed['mensagem_erro']
            ?? $mediaParsed['mensagem_sucesso']
            ?? $mediaParsed['mensagem_erro']
            ?? 'Resultado desconhecido';

        return json_encode([
            'success'        => $this->isSuccess($textResponse, $mediaResponse),
            'text_response'  => $textParsed,
            'media_response' => $mediaParsed,
            'message'        => $message,
        ]);
    }

    /**
     * Envio pela EvoApiService, buscando a instância configurada
     */
    protected function sendWithEvo(
        string $numero,
        ?string $mensagem,
        ?string $file,
        int $empresa_id
    ): string {
        // busca instância EVO do tenant
        $inst = EvoApiInstance::where('empresa_id', $empresa_id)
            ->firstOrFail();

        $textParsed  = null;
        $mediaParsed = null;

        // 1) Texto
        if (! empty($mensagem)) {
            $res = $this->evoService->sendText(
                $numero,
                $mensagem,
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! ($res['success'] ?? false)) {
                $textParsed = ['mensagem_erro' => $res['error'] ?? 'Falha ao enviar texto via Evo'];
                // já retorna erro imediato
                return json_encode([
                    'success'        => false,
                    'text_response'  => $textParsed,
                    'media_response' => null,
                    'message'        => $textParsed['mensagem_erro'],
                ]);
            }
            $textParsed = ['mensagem_sucesso' => 'Texto enviado!'];
        }

        // 2) Arquivo em Base64
        if ($file) {
            if (Str::startsWith($file, ['http://','https://'])) {
                $rel  = parse_url($file, PHP_URL_PATH);
                $path = public_path(ltrim($rel, '/'));
            } else {
                $path = $file;
            }
            if (! file_exists($path)) {
                $mediaParsed = ['mensagem_erro' => "Arquivo não encontrado: {$path}"];
                return json_encode([
                    'success'        => false,
                    'text_response'  => $textParsed,
                    'media_response' => $mediaParsed,
                    'message'        => $mediaParsed['mensagem_erro'],
                ]);
            }

            $b64      = base64_encode(safe_file_get_contents($path));
            $filename = basename($path);

            $res = $this->evoService->sendBase64(
                $numero,
                $b64,
                $filename,
                'document',
                '',
                $inst->name,
                $inst->base_url,
                $inst->api_key
            );
            if (! ($res['success'] ?? false)) {
                $mediaParsed = ['mensagem_erro' => $res['error'] ?? 'Falha ao enviar mídia via Evo'];
                return json_encode([
                    'success'        => false,
                    'text_response'  => $textParsed,
                    'media_response' => $mediaParsed,
                    'message'        => $mediaParsed['mensagem_erro'],
                ]);
            }
            $mediaParsed = ['mensagem_sucesso' => 'Arquivo enviado!'];
        }

        // 3) Monta resposta de sucesso
        return json_encode([
            'success'        => true,
            'text_response'  => $textParsed,
            'media_response' => $mediaParsed,
            'message'        => 'Mensagem enviada com sucesso',
        ]);
    }

    /* ——— Helpers do legado abaixo ——— */

    private function forcePublicPath(string $url): string
    {
        $relative = parse_url($url, PHP_URL_PATH);
        return public_path(ltrim($relative, '/'));
    }

    private function sendTextMessage(string $numero, string $mensagem, string $token, string $endpoint): string
    {
        $data    = ['number'=>$numero, 'body'=>$mensagem];
        $headers = ['Authorization: Bearer '.$token, 'Content-Type: application/json'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER,   $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,    json_encode($data));
        curl_setopt($ch, CURLOPT_URL,           $endpoint);
        curl_setopt($ch, CURLOPT_TIMEOUT,       30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $resp = curl_exec($ch);
        curl_close($ch);

        return $resp ?: '';
    }

    private function sendMediaMessage(string $numero, string $file, string $token, string $endpoint): string
    {
        $data = [
            'number' => $numero,
            'body'   => basename($file),
            'userId' => '',
            'queueId'=> '',
            'medias' => new \CURLFile($file, $this->getMimeType($file)),
        ];
        $headers = ['Authorization: Bearer '.$token, 'Content-Type: multipart/form-data'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER,   $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,    $data);
        curl_setopt($ch, CURLOPT_URL,           $endpoint);
        curl_setopt($ch, CURLOPT_TIMEOUT,       30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $resp = curl_exec($ch);
        curl_close($ch);

        return $resp ?: '';
    }

    private function formatPhoneNumber(string $numero): string
    {
        $n = preg_replace('/\D/', '', $numero);
        if (! preg_match('/^\d{12,13}$/', $n)) {
            throw new \InvalidArgumentException(
                "O telefone deve conter código do país + DDD + número"
            );
        }
        return $n;
    }

    private function processResponse(?string $response): array
    {
        $arr = json_decode($response ?? '', true) ?: [];
        if (isset($arr['mensagem'])) {
            return ['mensagem_sucesso'=>'Mensagem enviada!'];
        }
        if (isset($arr['error'])) {
            return ['mensagem_erro'=>'Falha ao enviar mensagem'];
        }
        return ['mensagem_erro'=>'Falha ao enviar mensagem'];
    }

    private function isSuccess(?string $textResponse, ?string $mediaResponse): bool
    {
        $t = json_decode($textResponse ?? '', true) ?: [];
        $m = $mediaResponse ? (json_decode($mediaResponse, true) ?: []) : [];
        if (isset($t['mensagem']) && (! $mediaResponse || isset($m['mensagem']))) {
            return true;
        }
        return false;
    }

    private function getMimeType(string $file): string
    {
        if (! file_exists($file)) {
            return 'application/octet-stream';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mime ?: 'application/octet-stream';
    }
}
