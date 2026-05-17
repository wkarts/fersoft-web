<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\SecurityFeatureService;
use App\Services\Security\SecurityHealthService;

class SecurityHealthController extends Controller
{
    public function index(SecurityFeatureService $featureService, SecurityHealthService $healthService)
    {
        if (!$featureService->isSuperAdmin()) {
            abort(403, 'Acesso restrito ao SuperAdmin.');
        }

        return view('security.health.index', [
            'title' => 'Diagnóstico da Segurança de Operações',
            'diagnostic' => $healthService->global(),
        ]);
    }

    public function company($empresaId, SecurityFeatureService $featureService, SecurityHealthService $healthService)
    {
        if (!$featureService->isSuperAdmin()) {
            abort(403, 'Acesso restrito ao SuperAdmin.');
        }

        return view('security.health.company', [
            'title' => 'Diagnóstico da Empresa',
            'diagnostic' => $healthService->company((int) $empresaId),
        ]);
    }
}
