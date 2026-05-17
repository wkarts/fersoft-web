<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Security\SecurityAuthorizer;
use App\Models\Security\SecurityCrudResource;
use App\Models\Usuario;
use App\Services\Security\SecurityAuthorizerService;
use App\Services\Security\SecurityCrudPermissionService;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;

class SecurityAuthorizerController extends Controller
{
    public function index(Request $request, SecurityFeatureService $featureService)
    {
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $isSuper && $request->filled('empresa_id') ? (int) $request->empresa_id : $featureService->currentEmpresaId();

        $authorizers = SecurityAuthorizer::query()
            ->with(['usuario', 'empresa', 'tokens'])
            ->when(!$isSuper, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when($isSuper && $request->filled('empresa_id'), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $usuarios = Usuario::query()
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->where('ativo', 1)
            ->orderBy('nome')
            ->get(['id', 'nome', 'login', 'empresa_id']);

        $resources = SecurityCrudResource::query()
            ->where('enabled', true)
            ->when(!$isSuper, function ($query) {
                $query->where('tenant_visible', true)->where('super_admin_only', false);
            })
            ->orderBy('module')
            ->orderBy('display_name')
            ->get();

        $empresas = $isSuper ? Empresa::query()->orderBy('nome')->get(['id', 'nome', 'cnpj']) : collect();

        return view('security.authorizers.index', [
            'title' => 'Autorizadores',
            'isSuper' => $isSuper,
            'empresaId' => $empresaId,
            'authorizers' => $authorizers,
            'usuarios' => $usuarios,
            'resources' => $resources,
            'empresas' => $empresas,
            'actions' => SecurityCrudPermissionService::ACTIONS,
        ]);
    }

    public function store(Request $request, SecurityFeatureService $featureService, SecurityAuthorizerService $authorizerService)
    {
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $isSuper ? ($request->empresa_id ?: null) : $featureService->currentEmpresaId();

        if (!$empresaId && !$isSuper) {
            session()->flash('mensagem_erro', 'Empresa atual não identificada para configurar autorizadores.');
            return redirect('/seguranca/autorizadores');
        }

        $data = $request->all();
        $data['empresa_id'] = $empresaId;

        if (!$request->filled('usuario_id')) {
            session()->flash('mensagem_erro', 'Informe o usuário autorizador.');
            return redirect()->back()->withInput();
        }

        $authorizerService->createOrUpdate($data);

        session()->flash('mensagem_sucesso', 'Autorizador salvo com sucesso.');
        return redirect('/seguranca/autorizadores' . ($empresaId && $isSuper ? '?empresa_id=' . $empresaId : ''));
    }

    public function destroy($id)
    {
        $authorizer = SecurityAuthorizer::findOrFail($id);
        $authorizer->delete();

        session()->flash('mensagem_sucesso', 'Autorizador removido com sucesso.');
        return redirect()->back();
    }
}
