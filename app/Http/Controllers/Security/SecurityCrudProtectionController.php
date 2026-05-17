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
        $query = $request->getQueryString();
        return redirect('/seguranca/regras' . ($query ? '?' . $query : ''));
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
        return redirect('/seguranca/regras' . ($empresaId && $isSuper ? '?empresa_id=' . $empresaId : ''));
    }

    public function destroy($id)
    {
        $rule = SecurityCrudProtectionRule::findOrFail($id);
        $rule->delete();

        session()->flash('mensagem_sucesso', 'Regra de proteção removida com sucesso.');
        return redirect()->back();
    }
}
