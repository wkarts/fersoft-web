<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectCronRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) env('CRON_MASTER_TOKEN', '');

        if ($expectedToken === '') {
            abort(403, 'CRON_MASTER_TOKEN não configurado.');
        }

        $receivedToken = (string) (
            $request->route('token')
            ?: $request->query('token')
            ?: $request->header('X-Cron-Token')
        );

        if ($receivedToken === '' || ! hash_equals($expectedToken, $receivedToken)) {
            abort(403, 'Token de cron inválido.');
        }

        $allowedIps = array_filter(array_map('trim', explode(',', (string) env('CRON_ALLOWED_IPS', ''))));

        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps, true)) {
            abort(403, 'IP não autorizado para executar o cron.');
        }

        return $next($request);
    }
}
