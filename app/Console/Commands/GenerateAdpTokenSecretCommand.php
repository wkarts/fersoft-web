<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateAdpTokenSecretCommand extends Command
{
    protected $signature = 'adp:generate-token-secret {--write : Grava ADP_GLOBAL_TOKEN_STORAGE e ADP_GLOBAL_TOKEN_SECRET no .env}';

    protected $description = 'Gera uma chave própria e estável para criptografia dos tokens globais ADP, independente da APP_KEY.';

    public function handle(): int
    {
        $secret = 'base64:' . base64_encode(random_bytes(32));

        $this->info('ADP_GLOBAL_TOKEN_STORAGE=custom_encrypted');
        $this->info('ADP_GLOBAL_TOKEN_SECRET=' . $secret);

        if ($this->option('write')) {
            $envPath = base_path('.env');
            if (!is_file($envPath)) {
                $this->error('.env não encontrado. Copie as linhas acima manualmente.');
                return self::FAILURE;
            }

            $env = file_get_contents($envPath);
            $env = $this->setEnvValue($env, 'ADP_GLOBAL_TOKEN_STORAGE', 'custom_encrypted');
            $env = $this->setEnvValue($env, 'ADP_GLOBAL_TOKEN_SECRET', $secret);
            file_put_contents($envPath, $env);
            $this->info('Chave ADP gravada no .env. Execute php artisan config:clear.');
        }

        return self::SUCCESS;
    }

    private function setEnvValue(string $env, string $key, string $value): string
    {
        $line = $key . '=' . $value;
        if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $env)) {
            return preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $env);
        }

        return rtrim($env) . PHP_EOL . $line . PHP_EOL;
    }
}
