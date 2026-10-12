<?php

namespace App\Support;

use App\Services\AppVersionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Identificacao unica da instalacao para requisicoes HTTP de saida.
 *
 * O nome vem de config('app.name') (APP_NAME) e a versao e resolvida
 * pelo mesmo AppVersionService utilizado no sistema.
 *
 * Este componente apenas gera o identificador. Nao altera automaticamente
 * requisicoes HTTP de terceiros nem o User-Agent do navegador.
 */
final class ApplicationUserAgent
{
    public static function value(): ?string
    {
        $nome = trim((string) config('app.name', ''));

        $identificador = trim(
            (string) preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '-',
                Str::ascii($nome)
            ),
            '-._'
        );

        if ($identificador === '') {
            return null;
        }

        try {
            $versao = trim((string) app(AppVersionService::class)->resolveInstalledVersion());
        } catch (Throwable $e) {
            $versao = '';

            Log::warning('Nao foi possivel identificar a versao instalada para o User-Agent.', [
                'error' => $e->getMessage(),
            ]);
        }

        $userAgent = $identificador;

        if ($versao !== '' && $versao !== '0.0.0') {
            $userAgent .= '/' . $versao;
        }

        $email = trim((string) config('mail.from.address', ''));

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $userAgent .= ' (' . $email . ')';
        }

        return $userAgent;
    }

    /**
     * Opcoes de identificacao permitidas ao fetch() do navegador.
     *
     * O navegador controla o cabecalho User-Agent: neste contexto,
     * a identificacao do site e feita pelo Referer contendo a origem.
     * Nenhum nome, versao ou dominio fica fixo no JavaScript.
     */
    public function browserFetchOptions(): array
    {
        return [
            'referrerPolicy' => 'origin',
        ];
    }
}
