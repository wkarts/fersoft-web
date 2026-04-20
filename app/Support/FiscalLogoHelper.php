<?php

namespace App\Support;

class FiscalLogoHelper
{
    public static function buildDataUri(?string $fileName): ?string
    {
        $fileName = trim((string)($fileName ?? ''));
        if ($fileName === '') {
            return null;
        }

        return self::buildDataUriFromPath(public_path('logos/' . $fileName));
    }

    public static function buildDataUriFromPath(?string $path): ?string
    {
        $path = trim((string)($path ?? ''));
        if ($path === '') {
            return null;
        }

        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $content = safe_file_get_contents($path);
        if ($content === false || $content === '' || $content === null) {
            return null;
        }

        if (function_exists('getimagesizefromstring')) {
            $imgInfo = @getimagesizefromstring($content);
            if ($imgInfo === false || empty($imgInfo['mime'])) {
                return null;
            }
            $mime = strtolower((string)$imgInfo['mime']);
        } else {
            $mime = null;
            if (function_exists('finfo_open')) {
                $f = @finfo_open(FILEINFO_MIME_TYPE);
                if ($f) {
                    $mime = @finfo_file($f, $path) ?: null;
                    @finfo_close($f);
                }
            }

            if (!$mime && function_exists('mime_content_type')) {
                $mime = @mime_content_type($path) ?: null;
            }

            $mime = strtolower((string)$mime);
        }

        $allowed = [
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/x-ms-bmp',
        ];

        if ($mime === '' || !in_array($mime, $allowed, true)) {
            return null;
        }

        return 'data://' . $mime . ';base64,' . base64_encode($content);
    }
}
