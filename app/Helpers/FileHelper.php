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


if (!function_exists('safe_file_put_contents')) {
    function safe_file_put_contents($filename, $data, $flags = 0, $context = null)
    {
        if ($filename === null || $filename === '') {
            return false;
        }

        $isLocalFile = is_string($filename) && !str_contains($filename, '://');
        if ($isLocalFile) {
            $normalized = str_replace('\\', DIRECTORY_SEPARATOR, $filename);
            $directory = dirname($normalized);
            if (!is_dir($directory)) {
                $created = @mkdir($directory, 0755, true);
                if (!$created && !is_dir($directory)) {
                    Log::warning('safe_file_put_contents não conseguiu criar diretório', [
                        'filename' => $filename,
                        'directory' => $directory,
                    ]);
                    return false;
                }
            }

            if (!is_writable($directory)) {
                Log::warning('safe_file_put_contents diretório sem permissão de escrita', [
                    'filename' => $filename,
                    'directory' => $directory,
                ]);
                return false;
            }
        }

        $written = $context === null
            ? @file_put_contents($filename, $data, $flags)
            : @file_put_contents($filename, $data, $flags, $context);

        if ($written === false) {
            Log::warning('safe_file_put_contents falhou ao gravar arquivo', [
                'filename' => $filename,
            ]);
        }

        return $written;
    }
}
