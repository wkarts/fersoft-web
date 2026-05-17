<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\SecurityFeatureService;

class SecurityAdminDashboardController extends Controller
{
    public function index(SecurityFeatureService $featureService)
    {
        if (!$featureService->isSuperAdmin()) {
            return redirect('/403');
        }

        return view('security.admin.global', [
            'title' => 'Painel Global de Segurança',
            'summary' => $featureService->globalSummary(),
        ]);
    }
}
