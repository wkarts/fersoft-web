<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Prepares Connect|API webhook payloads for durable event storage.
 * Transient QR pairing values and image payloads are not needed by async jobs.
 */
final class ConnectApiWebhookPayloadSanitizer
{
    private const REMOVED_MARKER = '[CONTEUDO_TRANSITORIO_REMOVIDO]';

    private const QR_CONTAINERS = ['qr', 'qrcode', 'qr_code', 'pairing_qr'];
    private const QR_SECRETS = ['code', 'base64', 'data', 'image', 'value', 'qr', 'qrcode', 'qr_code'];

    public static function sanitize(array $payload): array
    {
        $payload = BinaryPayloadSanitizer::sanitize($payload, self::REMOVED_MARKER);
        $result = self::sanitizeNode($payload);

        return is_array($result) ? $result : [];
    }

    private static function sanitizeNode(mixed $value, bool $insideQr = false, int $depth = 0): mixed
    {
        if ($depth > 16) {
            return $value;
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $child) {
                $normalizedKey = strtolower((string) $key);
                $normalizedKey = preg_replace('/[^a-z0-9]+/', '_', $normalizedKey) ?? $normalizedKey;
                $isQrContainer = $insideQr || in_array($normalizedKey, self::QR_CONTAINERS, true);
                $directQrValue = in_array($normalizedKey, self::QR_CONTAINERS, true)
                    && is_string($child)
                    && trim($child) !== '';

                if ($directQrValue || ($insideQr && in_array($normalizedKey, self::QR_SECRETS, true) && is_string($child) && trim($child) !== '')) {
                    $result[$key] = self::REMOVED_MARKER;
                    continue;
                }

                $result[$key] = self::sanitizeNode($child, $isQrContainer, $depth + 1);
            }

            return $result;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed !== '' && in_array($trimmed[0], ['{', '[', '"'], true)) {
                $decoded = json_decode($trimmed, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $sanitized = self::sanitizeNode($decoded, $insideQr, $depth + 1);

                    if ($sanitized !== $decoded) {
                        $encoded = json_encode(
                            $sanitized,
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
                        );

                        return $encoded === false ? self::REMOVED_MARKER : $encoded;
                    }
                }
            }
        }

        return $value;
    }
}
