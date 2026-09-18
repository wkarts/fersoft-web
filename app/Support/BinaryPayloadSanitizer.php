<?php


namespace App\Support;

/**
 * Remove conteúdo binário/Base64 de estruturas que serão persistidas ou auditadas.
 *
 * O Base64 continua permitido durante o transporte ADP -> navegador -> request.
 * Depois da persistência física da imagem, apenas referências pequenas devem seguir
 * para Models, metadados e auditoria.
 */
final class BinaryPayloadSanitizer
{
    private const REMOVED_MARKER = '[BASE64_REMOVIDO_APOS_PERSISTENCIA]';

    private const BINARY_KEYS = [
        'base64',
        'image_base64',
        'imagem_base64',
        'photo_base64',
        'foto_base64',
        'snapshot_base64',
        'file_base64',
        'jpeg_base64',
        'jpg_base64',
        'png_base64',
        'image_data_url',
        'imagem_data_url',
        'data_url',
        'image_src',
        'imagem_src',
        'image',
        'imagem',
        'photo',
        'foto',
    ];

    public static function sanitize(mixed $value, ?string $replacement = null): mixed
    {
        if (is_array($value)) {
            return self::sanitizeArray($value, $replacement);
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return self::sanitize($value->toArray(), $replacement);
            }

            if ($value instanceof \JsonSerializable) {
                return self::sanitize($value->jsonSerialize(), $replacement);
            }

            return self::sanitize(get_object_vars($value), $replacement);
        }

        if (is_string($value)) {
            return self::sanitizeString($value, null, $replacement);
        }

        return $value;
    }

    public static function sanitizeJsonString(?string $json, ?string $replacement = null): ?string
    {
        if ($json === null || trim($json) === '') {
            return $json;
        }

        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $sanitized = self::sanitizeString($json, null, $replacement);
            return is_string($sanitized) ? $sanitized : self::encode($sanitized);
        }

        return self::encode(self::sanitize($decoded, $replacement));
    }

    private static function sanitizeArray(array $data, ?string $replacement = null): array
    {
        $localReplacement = self::directReference($data) ?: $replacement;
        $result = [];

        foreach ($data as $key => $value) {
            $keyString = strtolower((string)$key);

            if (is_array($value)) {
                $result[$key] = self::sanitizeArray($value, $localReplacement);
                continue;
            }

            if (is_string($value)) {
                $result[$key] = self::sanitizeString($value, $keyString, $localReplacement);
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function sanitizeString(string $value, ?string $key, ?string $replacement): mixed
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return $value;
        }

        // Detecta assinaturas Base64 de formatos de imagem mesmo quando o legado
        // não usa prefixo data:image e o campo não se chama explicitamente base64.
        if (self::looksLikeKnownImageBase64($trimmed)) {
            return $replacement ?: self::REMOVED_MARKER;
        }

        if (self::isBinaryKey($key) && self::looksLikeImagePayload($trimmed)) {
            return $replacement ?: self::REMOVED_MARKER;
        }

        if (str_starts_with(strtolower($trimmed), 'data:image/') && str_contains($trimmed, ';base64,')) {
            return $replacement ?: self::REMOVED_MARKER;
        }

        // Alguns campos do legado guardam JSON (às vezes mais de uma vez) dentro
        // de outra estrutura JSON. Mantemos o tipo string, mas saneamos por dentro.
        if (strlen($trimmed) >= 2 && in_array($trimmed[0], ['{', '[', '"'], true)) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return self::encode(self::sanitize($decoded, $replacement));
            }
        }

        return $value;
    }

    private static function looksLikeKnownImageBase64(string $value): bool
    {
        if (strlen($value) < 64) {
            return false;
        }

        // Assinaturas binárias usuais codificadas em Base64.
        // JPEG: /9j/ | PNG: iVBORw0KGgo | GIF: R0lGOD | WebP: UklGR | BMP: Qk
        return str_starts_with($value, '/9j/')
            || str_starts_with($value, 'iVBORw0KGgo')
            || str_starts_with($value, 'R0lGOD')
            || str_starts_with($value, 'UklGR')
            || str_starts_with($value, 'Qk');
    }

    private static function looksLikeImagePayload(string $value): bool
    {
        if (preg_match('~^data:image/[a-z0-9.+-]+;base64,~i', $value)) {
            return true;
        }

        if (preg_match('~^(https?://|/|[a-zA-Z]:[\\\\/])~', $value)) {
            return false;
        }

        if (strlen($value) < 4096) {
            return false;
        }

        // Analisa apenas uma amostra para não duplicar strings gigantes em memória.
        $sample = substr($value, 0, 4096);

        return preg_match('/^[A-Za-z0-9+\/\r\n]+={0,2}$/', $sample) === 1;
    }

    private static function isBinaryKey(?string $key): bool
    {
        if ($key === null || $key === '') {
            return false;
        }

        if (in_array($key, self::BINARY_KEYS, true)) {
            return true;
        }

        return str_contains($key, 'base64') || str_contains($key, 'data_url');
    }

    private static function directReference(array $data): ?string
    {
        foreach (['arquivo_path', 'file_path', 'image_path', 'imagem_path', 'snapshot_path'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && trim($data[$key]) !== '') {
                return trim($data[$key]);
            }
        }

        $storagePath = is_array($data['storage'] ?? null)
            ? ($data['storage']['arquivo_path'] ?? null)
            : null;
        if (is_string($storagePath) && trim($storagePath) !== '') {
            return trim($storagePath);
        }

        foreach (['arquivo_url', 'file_url', 'image_url', 'imagem_url', 'snapshot_url'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && trim($data[$key]) !== '') {
                return trim($data[$key]);
            }
        }

        return null;
    }

    private static function encode(mixed $value): string
    {
        $encoded = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return $encoded === false ? 'null' : $encoded;
    }
}
