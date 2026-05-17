<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Security\SecurityAuditPolicy;
use App\Models\Security\SecurityCrudResource;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;

class SecurityAuditPolicyController extends Controller
{
    public function __construct(protected SecurityFeatureService $featureService) {}

    public function index(Request $request)
    {
        $isSuper = $this->featureService->isSuperAdmin();
        $empresaId = $isSuper && $request->filled('empresa_id')
            ? (int) $request->empresa_id
            : $this->featureService->currentEmpresaId();

        $policies = SecurityAuditPolicy::query()
            ->with(['resource', 'empresa'])
            ->when(!$isSuper, fn ($query) => $query->where('empresa_id', $empresaId))
            ->when($isSuper && $request->filled('empresa_id'), fn ($query) => $query->where('empresa_id', $empresaId))
            ->orderBy('model_class')
            ->get();

        $resources = SecurityCrudResource::query()
            ->where('enabled', true)
            ->orderBy('module')
            ->orderBy('display_name')
            ->get();

        $empresas = $isSuper
            ? Empresa::query()->orderBy('nome')->get()
            : collect();

        return view('security.audit.policies.index', compact('policies', 'resources', 'empresas', 'empresaId', 'isSuper'));
    }

    public function store(Request $request)
    {
        $isSuper = $this->featureService->isSuperAdmin();
        $empresaId = $isSuper && $request->filled('empresa_id')
            ? (int) $request->empresa_id
            : $this->featureService->currentEmpresaId();

        $data = $request->validate([
            'security_crud_resource_id' => 'nullable|integer',
            'model_class' => 'nullable|string|max:255',
            'tenant_can_view' => 'nullable|boolean',
            'tenant_can_view_json' => 'nullable|boolean',
            'tenant_can_export_json' => 'nullable|boolean',
            'tenant_can_restore' => 'nullable|boolean',
            'super_admin_only' => 'nullable|boolean',
            'sanitize_fields' => 'nullable|string',
            'enabled' => 'nullable|boolean',
        ]);

        $resource = null;
        if (!empty($data['security_crud_resource_id'])) {
            $resource = SecurityCrudResource::find((int) $data['security_crud_resource_id']);
        }

        $modelClass = $resource?->model_class ?: trim((string) ($data['model_class'] ?? ''));

        if ($modelClass === '') {
            return back()->with('mensagem_erro', 'Informe um recurso ou uma model.')->withInput();
        }

        $sanitizeFields = collect(explode(',', (string) ($data['sanitize_fields'] ?? '')))
            ->map(fn ($field) => trim($field))
            ->filter()
            ->values()
            ->all();

        SecurityAuditPolicy::updateOrCreate(
            [
                'empresa_id' => $empresaId,
                'model_class' => $modelClass,
            ],
            [
                'security_crud_resource_id' => $resource?->id,
                'tenant_can_view' => $request->boolean('tenant_can_view'),
                'tenant_can_view_json' => $request->boolean('tenant_can_view_json'),
                'tenant_can_export_json' => $request->boolean('tenant_can_export_json'),
                'tenant_can_restore' => $request->boolean('tenant_can_restore'),
                'super_admin_only' => $request->boolean('super_admin_only'),
                'sanitize_fields' => $sanitizeFields,
                'enabled' => $request->has('enabled'),
            ]
        );

        return back()->with('mensagem_sucesso', 'Política de auditoria salva com sucesso.');
    }

    public function destroy(int $id)
    {
        $isSuper = $this->featureService->isSuperAdmin();
        $empresaId = $this->featureService->currentEmpresaId();

        $policy = SecurityAuditPolicy::query()
            ->when(!$isSuper, fn ($query) => $query->where('empresa_id', $empresaId))
            ->findOrFail($id);

        $policy->delete();

        return back()->with('mensagem_sucesso', 'Política de auditoria removida com sucesso.');
    }
}
