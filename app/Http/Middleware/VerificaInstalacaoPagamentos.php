<?php

namespace App\Http\Middleware;

use App\Services\Pagamentos\Estabelecimento;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificaInstalacaoPagamentos
{
    public function handle(Request $request, Closure $next)
    {
        if (!Estabelecimento::integracaoInstalada()) {
            return new JsonResponse(['message' => 'Integração de pagamentos eletrônicos indisponível.'], 503);
        }

        return $next($request);
    }
}
