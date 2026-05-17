<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Security\EmpresaSecuritySetting;
use App\Models\Security\SecurityAuthorizer;
use App\Models\Security\SecurityAuthorizerToken;
use App\Models\Security\SecurityCrudPermission;
use App\Models\Security\SecurityCrudProtectionRule;
use App\Models\Security\SecurityCrudResource;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;

class SecurityAdminCompanyController extends Controller
{
    public function index(Request $request, SecurityFeatureService $featureService)
    {
        if (!$featureService->isSuperAdmin()) {
            abort(403, 'Acesso restrito ao SuperAdmin.');
        }

        $empresas = Empresa::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim($request->search) . '%';
                $query->where(function ($builder) use ($search) {
                    $builder->where('nome', 'like', $search)
                        ->orWhere('nome_fantasia', 'like', $search)
                        ->orWhere('cnpj', 'like', $search);
                });
            })
            ->orderBy('nome')
            ->paginate(30)
            ->withQueryString();

        $settings = EmpresaSecuritySetting::query()
            ->whereIn('empresa_id', $empresas->pluck('id'))
            ->get()
            ->keyBy('empresa_id');

        return view('security.admin.companies.index', [
            'title' => 'Segurança por Empresa',
            'empresas' => $empresas,
            'settings' => $settings,
        ]);
    }

    public function show($empresaId, SecurityFeatureService $featureService)
    {
        if (!$featureService->isSuperAdmin()) {
            abort(403, 'Acesso restrito ao SuperAdmin.');
        }

        $empresa = Empresa::findOrFail((int) $empresaId);
        $setting = EmpresaSecuritySetting::firstOrCreate(['empresa_id' => $empresa->id]);

        return view('security.admin.companies.show', [
            'title' => 'Painel de Segurança da Empresa',
            'empresa' => $empresa,
            'setting' => $setting,
            'resourcesCount' => SecurityCrudResource::count(),
            'permissionsCount' => SecurityCrudPermission::where('empresa_id', $empresa->id)->count(),
            'rulesCount' => SecurityCrudProtectionRule::where('empresa_id', $empresa->id)->count(),
            'authorizersCount' => SecurityAuthorizer::where('empresa_id', $empresa->id)->count(),
            'tokensCount' => SecurityAuthorizerToken::where('empresa_id', $empresa->id)->count(),
        ]);
    }
}
