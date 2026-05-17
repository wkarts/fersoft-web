<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Security\SecurityCrudProtectionRule;
use App\Models\Security\SecurityCrudResource;
use App\Services\Security\SecurityCrudPermissionService;
use App\Services\Security\SecurityCrudProtectionService;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;

class SecurityCrudProtectionController extends Controller
{
    public function index(Request $request, SecurityFeatureService $featureService)
    {
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $isSuper && $request->filled('empresa_id') ? (int) $request->empresa_id : $featureService->currentEmpresaId();

        $resources = SecurityCrudResource::query()
            ->where('enabled', true)
            ->when(!$isSuper, function ($query) {
                $query->where('tenant_visible', true)->where('super_admin_only', false);
            })
            ->orderBy('module')
            ->orderBy('display_name')
            ->get();

        $rules = SecurityCrudProtectionRule::query()
            ->with(['resource', 'empresa'])
            ->when(!$isSuper, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when($isSuper && $request->filled('empresa_id'), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when($request->filled('resource_id'), function ($query) use ($request) {
                $query->where('security_crud_resource_id', (int) $request->resource_id);
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $empresas = $isSuper ? Empresa::query()->orderBy('nome')->get(['id', 'nome', 'cnpj']) : collect();

        return view('security.protections.index', [
            'title' => 'Proteções de Operação',
            'isSuper' => $isSuper,
            'empresaId' => $empresaId,
            'resources' => $resources,
            'rules' => $rules,
            'empresas' => $empresas,
            'actions' => SecurityCrudPermissionService::ACTIONS,
            'protectionTypes' => SecurityCrudProtectionService::PROTECTION_TYPES,
        ]);
    }

    public function store(Request $request, SecurityFeatureService $featureService, SecurityCrudProtectionService $protectionService)
    {
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $isSuper ? ($request->empresa_id ?: null) : $featureService->currentEmpresaId();

        if (!$empresaId && !$isSuper) {
            session()->flash('mensagem_erro', 'Empresa atual não identificada para configurar proteções.');
            return redirect('/seguranca/protecoes');
        }

        $data = $request->all();
        $data['empresa_id'] = $empresaId;

        $protectionService->createOrUpdate($data);

        session()->flash('mensagem_sucesso', 'Regra de proteção salva com sucesso.');
        return redirect('/seguranca/protecoes' . ($empresaId && $isSuper ? '?empresa_id=' . $empresaId : ''));
    }

    public function destroy($id)
    {
        $rule = SecurityCrudProtectionRule::findOrFail($id);
        $rule->delete();

        session()->flash('mensagem_sucesso', 'Regra de proteção removida com sucesso.');
        return redirect()->back();
    }
}
