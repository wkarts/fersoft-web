<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use App\Support\BinaryPayloadSanitizer;

class LogService
{
    protected $empresaId;
    protected $usuarioId;
    protected $filialId;

    /**
     * Fingerprints já persistidos durante a request/processo atual.
     *
     * Serve como última barreira contra duas chamadas manuais idênticas ao
     * LogService na mesma execução. Não faz SELECT no banco e, portanto, não
     * adiciona custo de leitura ao fluxo normal.
     */
    protected static array $requestFingerprints = [];
    protected static ?int $requestScopeId = null;

    protected const MAX_REQUEST_FINGERPRINTS = 500;

    public function __construct($empresaId, $usuarioId, $filialId)
    {
        $this->empresaId = $empresaId;
        $this->usuarioId = $usuarioId;
        $this->filialId = $filialId;
    }

    /**
     * Registra um evento de auditoria no banco.
     *
     * Retorna o model criado ou null quando a entrada é vazia/duplicada.
     */
    public function registrar(string $acao, ?string $modelo, array $dados = [])
    {
        try {
            $registroId = $dados['registro_id'] ?? null;

            $dadosAntes = isset($dados['dados_antes'])
                ? $this->formatarJson(BinaryPayloadSanitizer::sanitize($dados['dados_antes']))
                : null;
            $dadosDepois = isset($dados['dados_depois'])
                ? $this->formatarJson(BinaryPayloadSanitizer::sanitize($dados['dados_depois']))
                : null;

            // Não persiste update sem alteração real.
            if ($acao === 'update' && $dadosAntes !== null && $dadosAntes === $dadosDepois) {
                return null;
            }

            $empresaId = in_array($this->empresaId, [null, 'null'], true) ? null : (int) $this->empresaId;
            $usuarioId = in_array($this->usuarioId, [null, 'null'], true) ? null : (int) $this->usuarioId;
            $filialId = in_array($this->filialId, [null, 'null', -1, '-1'], true) ? null : (int) $this->filialId;

            $this->prepareRequestFingerprintScope();

            $fingerprint = $this->fingerprint([
                'empresa_id' => $empresaId,
                'usuario_id' => $usuarioId,
                'filial_id' => $filialId,
                'acao' => $acao,
                'modelo' => $modelo,
                'registro_id' => $registroId,
                'dados_anteriores' => $dadosAntes,
                'dados_depois' => $dadosDepois,
            ]);

            if (isset(static::$requestFingerprints[$fingerprint])) {
                return null;
            }

            static::$requestFingerprints[$fingerprint] = true;
            if (count(static::$requestFingerprints) > static::MAX_REQUEST_FINGERPRINTS) {
                // Mantém memória limitada em workers/comandos longos.
                static::$requestFingerprints = array_slice(
                    static::$requestFingerprints,
                    -static::MAX_REQUEST_FINGERPRINTS,
                    null,
                    true
                );
            }

            return Log::create([
                'empresa_id' => $empresaId,
                'usuario_id' => $usuarioId,
                'filial_id' => $filialId,
                'acao' => $acao,
                'modelo' => $modelo,
                'dados_anteriores' => $dadosAntes,
                'dados_depois' => $dadosDepois,
                'ip_address' => Request::ip(),
                'user_agent' => Request::header('User-Agent'),
                'token' => Str::uuid()->toString(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Erro ao registrar log de atividade', [
                'erro' => $e->getMessage(),
                'acao' => $acao,
                'modelo' => $modelo,
            ]);

            return null;
        }
    }

    /**
     * Reinicia a barreira de duplicidade quando muda o objeto Request.
     * Isso evita que workers longos (queue/Octane/comandos) carreguem fingerprints
     * de uma execução anterior e silenciem um evento legítimo futuro.
     */
    protected function prepareRequestFingerprintScope(): void
    {
        try {
            if (!function_exists('app') || !app()->bound('request')) {
                // Fora de HTTP não usamos deduplicação estática entre execuções.
                static::$requestFingerprints = [];
                static::$requestScopeId = null;
                return;
            }

            $request = app('request');
            $scopeId = is_object($request) ? spl_object_id($request) : null;

            if ($scopeId === null || static::$requestScopeId !== $scopeId) {
                static::$requestFingerprints = [];
                static::$requestScopeId = $scopeId;
            }
        } catch (\Throwable $e) {
            static::$requestFingerprints = [];
            static::$requestScopeId = null;
        }
    }

    protected function fingerprint(array $payload): string
    {
        $normalized = $this->normalizeForFingerprint($payload);

        return hash(
            'sha256',
            json_encode(
                $normalized,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            ) ?: serialize($normalized)
        );
    }

    protected function normalizeForFingerprint($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeForFingerprint($item);
        }

        return $value;
    }

    /**
     * Converte strings JSON em array e deixa a serialização final para o cast
     * da model Log, evitando JSON duplamente serializado.
     */
    protected function formatarJson($data): mixed
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            return ['valor' => $data];
        }

        if (is_array($data)) {
            return $data;
        }

        return null;
    }
}
