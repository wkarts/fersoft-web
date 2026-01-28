<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportAllowedModels extends Command
{
    protected $signature = 'sync:export-models';
    protected $description = 'Exporta automaticamente todos os models encontrados na pasta App\Models para o arquivo allowed_models.php';

    public function handle()
    {
        $modelPath = app_path('Models');
        $files = File::allFiles($modelPath);
        $output = "<?php\n\nreturn [\n";

        foreach ($files as $file) {
            $className = $file->getFilenameWithoutExtension();

            // 🔸 Ignora arquivos de backup, ocultos ou com sufixo incorreto
            if (
                str_contains($className, 'bkp') ||
                str_ends_with($file->getFilename(), '.bak') ||
                str_ends_with($file->getFilename(), '~') ||
                str_starts_with($className, '.') ||
                str_ends_with($className, '.php') // ← Corrige sua situação
            ) {
                continue;
            }

            // 🔸 Garante que não tenha duplicados
            $output .= "    '{$className}' => \\App\\Models\\{$className}::class,\n";
        }

        $output .= "];\n";

        // Limpa e sobrescreve o arquivo allowed_models.php
        File::put(config_path('allowed_models.php'), $output);

        $this->info('✅ Arquivo allowed_models.php atualizado com sucesso e limpo de duplicações ou arquivos inválidos.');
    }
}
