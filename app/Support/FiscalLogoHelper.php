<?php

namespace App\Support;

class FiscalLogoHelper
{
    public static function buildDataUri(?string $fileName): ?string
    {
        $fileName = trim((string) ($fileName ?? ''));
        if ($fileName === '') {
            return null;
        }

        // Compatível com chamadas legadas: ora vem só o nome do arquivo,
        // ora caminho relativo/absoluto.
        if (self::isAbsolutePath($fileName)) {
            return self::buildDataUriFromPath($fileName);
        }

        $normalized = ltrim(str_replace('\\', '/', $fileName), '/');

        $candidates = [
            public_path('logos/' . basename($normalized)),
            public_path($normalized),
            base_path('public/logos/' . basename($normalized)),
            base_path('public/' . $normalized),
            base_path('public/public/logos/' . basename($normalized)),
            base_path($normalized),
            $normalized,
        ];

        foreach ($candidates as $candidate) {
            $uri = self::buildDataUriFromPath($candidate);
            if ($uri !== null) {
                return $uri;
            }
        }

        return null;
    }

    public static function buildDataUriFromPath(?string $path): ?string
    {
        $path = trim((string) ($path ?? ''));
        if ($path === '') {
            return null;
        }

        // Tenta resolver caminhos relativos para manter compatibilidade entre ambientes.
        if (!self::isAbsolutePath($path)) {
            $normalized = ltrim(str_replace('\\', '/', $path), '/');
            $resolvedCandidates = [
                public_path($normalized),
                base_path('public/' . $normalized),
                base_path($normalized),
                $normalized,
            ];

            foreach ($resolvedCandidates as $candidate) {
                if (is_file($candidate) && is_readable($candidate)) {
                    $path = $candidate;
                    break;
                }
            }
        }

        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $content = safe_file_get_contents($path);
        if ($content === false || $content === '' || $content === null) {
            return null;
        }

        // Mantém o padrão legado utilizado nos controladores fiscais
        // (compatível com os renderizadores atuais de DANFE/DANFCE).
        return 'data://text/plain;base64,' . base64_encode($content);
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }
}
