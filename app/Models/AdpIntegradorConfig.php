<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class AdpIntegradorConfig extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'adp_integrador_configs';

    protected $fillable = [
        'empresa_id', 'descricao', 'base_url', 'global_token', 'global_token_enabled',
        'global_token_type', 'global_token_header', 'timeout_ms', 'ativo',
    ];

    protected $casts = [
        'global_token_enabled' => 'boolean',
        'ativo' => 'boolean',
    ];

    private const PLAIN_PREFIX = 'adpplain:v1:';
    private const CUSTOM_PREFIX = 'adpenc:v1:';

    /**
     * IMPORTANTE:
     *
     * Não use o cast nativo "encrypted" neste campo.
     *
     * O token global do ADP não pode depender da APP_KEY do Laravel, porque em alguns
     * ambientes a rotina operacional executa `php artisan key:generate --force` junto
     * com limpeza de cache. Qualquer dado criptografado pelo Crypt/encrypted cast fica
     * ilegível após troca da APP_KEY e passa a gerar "The MAC is invalid".
     *
     * Esta implementação é tolerante e estável:
     * - tokens novos são salvos por padrão como base64 versionado, sem depender da APP_KEY;
     * - se ADP_GLOBAL_TOKEN_STORAGE=custom_encrypted e ADP_GLOBAL_TOKEN_SECRET estiver definido,
     *   usa criptografia OpenSSL com segredo estável próprio do ADP;
     * - tokens antigos em texto puro continuam legíveis;
     * - tokens antigos criptografados pelo Laravel continuam legíveis enquanto a APP_KEY antiga
     *   ainda for válida;
     * - tokens Laravel inválidos não derrubam salvar, editar, listar, excluir ou inativar config.
     */
    public function getGlobalTokenAttribute($value): ?string
    {
        return $this->decodeTokenTolerant($value);
    }

    public function setGlobalTokenAttribute($value): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            $this->attributes['global_token'] = null;
            return;
        }

        $this->attributes['global_token'] = $this->encodeTokenStable($value);
    }

    /**
     * Retorna o token global de forma segura para uso em runtime.
     */
    public function globalTokenSafe(): ?string
    {
        $raw = $this->attributes['global_token'] ?? null;
        return $this->decodeTokenTolerant($raw);
    }

    /**
     * Informa se o valor salvo parece um token antigo do Laravel que não foi possível abrir.
     */
    public function globalTokenNeedsReset(): bool
    {
        $raw = $this->attributes['global_token'] ?? null;

        return is_string($raw)
            && $raw !== ''
            && $this->looksLikeLaravelEncryptedValue($raw)
            && $this->decodeLaravelEncryptedToken($raw) === null;
    }

    public function tokenMascarado(): ?string
    {
        $token = $this->globalTokenSafe();

        if (!$token) {
            return $this->globalTokenNeedsReset() ? 'TOKEN INVÁLIDO - REINFORME' : null;
        }

        return str_repeat('*', 8) . substr($token, -4);
    }

    /**
     * Regrava o token atual usando armazenamento estável independente da APP_KEY.
     */
    public function reencryptGlobalTokenIfReadable(): bool
    {
        $token = $this->globalTokenSafe();

        if (!$token) {
            return false;
        }

        $this->global_token = $token;
        return true;
    }

    private function encodeTokenStable(string $value): string
    {
        $mode = strtolower((string) env('ADP_GLOBAL_TOKEN_STORAGE', env('ADP_GLOBAL_TOKEN_SECRET') ? 'custom_encrypted' : 'plain'));
        $secret = trim((string) env('ADP_GLOBAL_TOKEN_SECRET', ''));

        if (in_array($mode, ['custom_encrypted', 'encrypted', 'openssl'], true) && $secret !== '' && function_exists('openssl_encrypt')) {
            $iv = random_bytes(16);
            $key = hash('sha256', $secret, true);
            $cipherText = openssl_encrypt($value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

            if ($cipherText !== false) {
                $mac = hash_hmac('sha256', $iv . $cipherText, $key, true);

                return self::CUSTOM_PREFIX . base64_encode(json_encode([
                    'iv' => base64_encode($iv),
                    'value' => base64_encode($cipherText),
                    'mac' => base64_encode($mac),
                ], JSON_UNESCAPED_SLASHES));
            }
        }

        return self::PLAIN_PREFIX . base64_encode($value);
    }

    private function decodeTokenTolerant($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (string) $value;

        if (str_starts_with($value, self::PLAIN_PREFIX)) {
            $decoded = base64_decode(substr($value, strlen(self::PLAIN_PREFIX)), true);
            return $decoded === false ? null : $decoded;
        }

        if (str_starts_with($value, self::CUSTOM_PREFIX)) {
            return $this->decodeCustomEncryptedToken($value);
        }

        if ($this->looksLikeLaravelEncryptedValue($value)) {
            return $this->decodeLaravelEncryptedToken($value);
        }

        // Compatibilidade com registros antigos gravados em texto puro.
        return $value;
    }

    private function decodeLaravelEncryptedToken(string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            Log::warning('Token ADP antigo criptografado com APP_KEY inválida. Reinforme o token da configuração ADP.', [
                'config_id' => $this->getKey(),
                'empresa_id' => $this->empresa_id ?? null,
            ]);

            return null;
        }
    }

    private function decodeCustomEncryptedToken(string $value): ?string
    {
        $secret = trim((string) env('ADP_GLOBAL_TOKEN_SECRET', ''));

        if ($secret === '' || !function_exists('openssl_decrypt')) {
            return null;
        }

        $payloadRaw = base64_decode(substr($value, strlen(self::CUSTOM_PREFIX)), true);
        if ($payloadRaw === false) {
            return null;
        }

        $payload = json_decode($payloadRaw, true);
        if (!is_array($payload)) {
            return null;
        }

        $iv = base64_decode((string) ($payload['iv'] ?? ''), true);
        $cipherText = base64_decode((string) ($payload['value'] ?? ''), true);
        $mac = base64_decode((string) ($payload['mac'] ?? ''), true);

        if ($iv === false || $cipherText === false || $mac === false) {
            return null;
        }

        $key = hash('sha256', $secret, true);
        $expectedMac = hash_hmac('sha256', $iv . $cipherText, $key, true);

        if (!hash_equals($expectedMac, $mac)) {
            return null;
        }

        $plain = openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $plain === false ? null : $plain;
    }

    private function looksLikeLaravelEncryptedValue(string $value): bool
    {
        $decoded = base64_decode($value, true);

        if ($decoded === false || $decoded === '') {
            return false;
        }

        $json = json_decode($decoded, true);

        return is_array($json)
            && array_key_exists('iv', $json)
            && array_key_exists('value', $json)
            && array_key_exists('mac', $json);
    }
}
