<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'sync/data',
        'whatsapp/ponto/webhook',
        'api/whatsapp/ponto/webhook',
        'whatsapp/ponto/webhook',
    ];
}
