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

        $permissions = SecurityCrudPermission::query()
            ->with(['resource', 'perfil', 'usuario', 'empresa'])
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

        $usuarios = Usuario::query()
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->get(['id', 'nome', 'login', 'empresa_id']);

        $perfis = PerfilAcesso::query()->orderBy('nome')->get(['id', 'nome']);
        $empresas = $isSuper ? Empresa::query()->orderBy('nome')->get(['id', 'nome', 'cnpj']) : collect();

        return view('security.permissions.index', [
            'title' => 'Permissões CRUD',
            'isSuper' => $isSuper,
            'empresaId' => $empresaId,
            'resources' => $resources,
            'permissions' => $permissions,
            'usuarios' => $usuarios,
            'perfis' => $perfis,
            'empresas' => $empresas,
            'actions' => SecurityCrudPermissionService::ACTIONS,
            'permissionService' => $permissionService,
        ]);
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
        return redirect('/seguranca/permissoes' . ($empresaId && $isSuper ? '?empresa_id=' . $empresaId : ''));
    }

    public function destroy($id)
    {
        $permission = SecurityCrudPermission::findOrFail($id);
        $permission->delete();

        session()->flash('mensagem_sucesso', 'Permissão removida com sucesso.');
        return redirect()->back();
    }
}
