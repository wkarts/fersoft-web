<?php

namespace App\Http\Middleware;

use App\Services\Security\SecurityFeatureService;
use Closure;
use Illuminate\Http\Request;

class EnsureSecuritySuperAdmin
{
    public function __construct(protected SecurityFeatureService $featureService) {}

    public function handle(Request $request, Closure $next)
    {
        if (!$this->featureService->isSuperAdmin()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Acesso restrito ao SuperAdmin da plataforma.',
                ], 403);
            }

            return redirect('/403');
        }

        return $next($request);
    }
}
