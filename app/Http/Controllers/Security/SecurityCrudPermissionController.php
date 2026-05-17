<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\PerfilAcesso;
use App\Models\Security\SecurityCrudPermission;
use App\Models\Security\SecurityCrudResource;
use App\Models\Usuario;
use App\Services\Security\SecurityCrudPermissionService;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;

class SecurityCrudPermissionController extends Controller
{
    public function index(Request $request, SecurityFeatureService $featureService, SecurityCrudPermissionService $permissionService)
    {
        $query = $request->getQueryString();
        return redirect('/seguranca/regras' . ($query ? '?' . $query : ''));
    }

    public function store(Request $request, SecurityFeatureService $featureService, SecurityCrudPermissionService $permissionService)
    {
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $isSuper ? ($request->empresa_id ?: null) : $featureService->currentEmpresaId();

        if (!$empresaId && !$isSuper) {
            session()->flash('mensagem_erro', 'Empresa atual não identificada para configurar permissões.');
            return redirect('/seguranca/permissoes');
        }

        $data = $request->all();
        $data['empresa_id'] = $empresaId;
        $data['perfil_acesso_id'] = $request->filled('perfil_acesso_id') ? (int) $request->perfil_acesso_id : null;
        $data['usuario_id'] = $request->filled('usuario_id') ? (int) $request->usuario_id : null;

        if (!$data['perfil_acesso_id'] && !$data['usuario_id']) {
            session()->flash('mensagem_erro', 'Informe um perfil ou usuário para aplicar a permissão.');
            return redirect()->back()->withInput();
        }

        $permissionService->createOrUpdate($data);

        session()->flash('mensagem_sucesso', 'Permissão CRUD salva com sucesso.');
        return redirect('/seguranca/regras' . ($empresaId && $isSuper ? '?empresa_id=' . $empresaId : ''));
    }

    public function destroy($id)
    {
        $permission = SecurityCrudPermission::findOrFail($id);
        $permission->delete();

        session()->flash('mensagem_sucesso', 'Permissão removida com sucesso.');
        return redirect()->back();
    }
}
