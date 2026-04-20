<?php

namespace App\Support;

class TransmissionMessageNormalizer
{
    public static function jsonOptions(): int
    {
        return JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }

    public static function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::normalize($v);
            }
            return $out;
        }

        if (is_object($value)) {
            if ($value instanceof \JsonSerializable) {
                return self::normalize($value->jsonSerialize());
            }
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $text = html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 | ENT_XML1, 'UTF-8');

        if (!mb_check_encoding($text, 'UTF-8')) {
            foreach (['Windows-1252', 'ISO-8859-1', 'ISO-8859-15'] as $enc) {
                try {
                    $converted = @mb_convert_encoding($text, 'UTF-8', $enc);
                    if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
                        $text = $converted;
                        break;
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\n{2,}/', "\n", $text);
        return trim((string) $text);
    }


    public static function prettyStructuredString(string $text): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return $text;
        }

        if (str_starts_with($trimmed, '<?xml') || (str_starts_with($trimmed, '<') && str_ends_with($trimmed, '>'))) {
            try {
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                if (@$dom->loadXML($trimmed)) {
                    return trim((string) $dom->saveXML());
                }
            } catch (\Throwable $e) {
            }
        }

        if ((str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) || (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))) {
            try {
                $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                return json_encode($decoded, self::jsonOptions()) ?: $text;
            } catch (\Throwable $e) {
            }
        }

        return $text;
    }

    public static function message(?int $cStat, ?string $xMotivo, ?string $fallback = null): string
    {
        $motivo = self::normalize($xMotivo ?? $fallback ?? 'Retorno indefinido');
        if ($cStat !== null) {
            return '[' . $cStat . '] - ' . $motivo;
        }
        return (string) $motivo;
    }
}
