<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class EnsureApplicationDirectories
{
    public static function handle(): void
    {
        $directories = [
            storage_path('app'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            try {
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
            } catch (\Throwable $e) {
                try {
                    Log::warning('Falha ao garantir diretório da aplicação.', [
                        'directory' => $directory,
                        'message' => $e->getMessage(),
                    ]);
                } catch (\Throwable $innerException) {
                    // Evita quebrar o bootstrap caso o log ainda não esteja disponível.
                }
            }
        }
    }
}
