<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('safe_file_get_contents')) {
    function safe_file_get_contents(
        $filename,
        $use_include_path = false,
        $context = null,
        $offset = 0,
        $length = null
    ) {
        if ($filename === null || $filename === '') {
            return false;
        }

        if (is_string($filename) && !str_contains($filename, '://')) {
            $normalized = str_replace('\\', DIRECTORY_SEPARATOR, $filename);
            if (!is_file($normalized) || !is_readable($normalized)) {
                Log::warning('safe_file_get_contents arquivo ausente ou sem leitura', [
                    'filename' => $filename,
                ]);
                return false;
            }
        }

        $content = $length === null
            ? @file_get_contents($filename, $use_include_path, $context, $offset)
            : @file_get_contents($filename, $use_include_path, $context, $offset, $length);

        if ($content === false) {
            Log::warning('safe_file_get_contents falhou ao ler conteúdo', [
                'filename' => $filename,
            ]);
        }

        return $content;
    }
}
