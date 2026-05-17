<?php

namespace App\Services\Security;

use App\Models\Empresa;
use App\Models\PerfilAcesso;
use App\Models\Security\SecurityCrudPermission;
use App\Models\Security\SecurityCrudProtectionRule;
use App\Models\Security\SecurityCrudResource;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SecurityRuleMatrixService
{
    public function pageData(Request $request): array
    {
        $feature = app(SecurityFeatureService::class);
        $isSuper = $feature->isSuperAdmin();
        $empresaId = $this->resolveEmpresaId($request, $feature, $isSuper);
        $scope = $this->resolveScope($request);

        $resources = $this->resources($request, $isSuper);
        $permissions = $this->permissions($empresaId, $scope, $resources->pluck('id')->all());
        $protections = $this->protections($empresaId, $resources->pluck('id')->all());
        $matrix = $this->matrix($resources, $permissions, $protections);

        return [
            'title' => 'Regras de Segurança por Módulo',
            'isSuper' => $isSuper,
            'empresaId' => $empresaId,
            'empresas' => $isSuper ? Empresa::query()->orderBy('nome')->get(['id', 'nome', 'cnpj']) : collect(),
            'usuarios' => $this->usuarios($empresaId),
            'perfis' => PerfilAcesso::query()->orderBy('nome')->get(['id', 'nome']),
            'resources' => $resources,
            'matrix' => $matrix,
            'scope' => $scope,
            'actions' => SecurityCrudPermissionService::ACTIONS,
            'protectionTypes' => SecurityCrudProtectionService::PROTECTION_TYPES,
            'modules' => SecurityCrudResource::query()
                ->where('enabled', true)
                ->when(!$isSuper, fn ($q) => $q->where('tenant_visible', true)->where('super_admin_only', false))
                ->orderBy('module')
                ->pluck('module')
                ->filter()
                ->unique()
                ->values(),
            'filters' => $request->only(['empresa_id', 'module', 'resource_id', 'q', 'scope_type', 'perfil_acesso_id', 'usuario_id']),
        ];
    }

    public function save(Request $request): array
    {
        $feature = app(SecurityFeatureService::class);
        $isSuper = $feature->isSuperAdmin();
        $empresaId = $isSuper ? ($request->filled('empresa_id') ? (int) $request->empresa_id : null) : $feature->currentEmpresaId();
        $resource = SecurityCrudResource::findOrFail((int) $request->security_crud_resource_id);
        $scope = $this->resolveScope($request);

        $permissionData = [
            'empresa_id' => $empresaId,
            'security_crud_resource_id' => $resource->id,
            'perfil_acesso_id' => $scope['perfil_acesso_id'],
            'usuario_id' => $scope['usuario_id'],
            'enabled' => $request->boolean('permission_enabled', true),
            'source' => $request->input('source', 'manual'),
        ];

        foreach (array_keys(SecurityCrudPermissionService::ACTIONS) as $action) {
            $permissionData['can_' . $action] = $request->boolean('permissions.' . $action);
        }

        app(SecurityCrudPermissionService::class)->createOrUpdate($permissionData);

        $savedProtections = 0;
        foreach (array_keys(SecurityCrudPermissionService::ACTIONS) as $action) {
            $protectionType = $request->input("protections.$action.protection_type", 'none');
            $enabled = $request->boolean("protections.$action.enabled", false);
            $message = $request->input("protections.$action.message");

            app(SecurityCrudProtectionService::class)->createOrUpdate([
                'empresa_id' => $empresaId,
                'security_crud_resource_id' => $resource->id,
                'action' => $action,
                'protection_type' => $protectionType,
                'requires_authorizer' => in_array($protectionType, ['authorizer_token', 'authorizer_otp', 'authorizer_token_or_otp'], true),
                'allow_self_authorization' => $request->boolean("protections.$action.allow_self_authorization", false),
                'bypass_super_admin' => $request->boolean("protections.$action.bypass_super_admin", true),
                'bypass_company_admin' => $request->boolean("protections.$action.bypass_company_admin", false),
                'enabled' => $enabled && $protectionType !== 'none',
                'message' => $message,
                'source' => $request->input('source', 'manual'),
            ]);
            $savedProtections++;
        }

        return ['permission' => 1, 'protections' => $savedProtections];
    }

    public function deletePermission(int $id, Request $request): void
    {
        $permission = SecurityCrudPermission::findOrFail($id);
        $this->assertTenantScope($permission->empresa_id, $request);
        $permission->delete();
    }

    public function deleteProtection(int $id, Request $request): void
    {
        $rule = SecurityCrudProtectionRule::findOrFail($id);
        $this->assertTenantScope($rule->empresa_id, $request);
        $rule->delete();
    }

    public function cleanup(Request $request): array
    {
        $feature = app(SecurityFeatureService::class);
        $isSuper = $feature->isSuperAdmin();
        $empresaId = $isSuper ? ($request->filled('empresa_id') ? (int) $request->empresa_id : null) : $feature->currentEmpresaId();
        $resourceId = $request->filled('security_crud_resource_id') ? (int) $request->security_crud_resource_id : null;
        $source = $request->input('source', 'all');
        $scope = $this->resolveScope($request);
        $cleanPermissions = $request->boolean('clean_permissions', true);
        $cleanProtections = $request->boolean('clean_protections', true);

        $permissionsDeleted = 0;
        $protectionsDeleted = 0;

        if ($cleanPermissions) {
            $query = SecurityCrudPermission::query();
            $this->applyCommonCleanupFilters($query, $empresaId, $resourceId, $source);
            if ($scope['scope_type'] === 'perfil') {
                $query->where('perfil_acesso_id', $scope['perfil_acesso_id'])->whereNull('usuario_id');
            } elseif ($scope['scope_type'] === 'usuario') {
                $query->where('usuario_id', $scope['usuario_id']);
            } else {
                $query->whereNull('perfil_acesso_id')->whereNull('usuario_id');
            }
            $permissionsDeleted = (clone $query)->count();
            $query->delete();
        }

        if ($cleanProtections) {
            $query = SecurityCrudProtectionRule::query();
            $this->applyCommonCleanupFilters($query, $empresaId, $resourceId, $source);
            $protectionsDeleted = (clone $query)->count();
            $query->delete();
        }

        return ['permissions' => $permissionsDeleted, 'protections' => $protectionsDeleted];
    }

    public function recreateDefaults(Request $request): array
    {
        $feature = app(SecurityFeatureService::class);
        $isSuper = $feature->isSuperAdmin();
        $empresaId = $isSuper ? ($request->filled('empresa_id') ? (int) $request->empresa_id : null) : $feature->currentEmpresaId();
        $setup = app(SecuritySetupService::class);

        if ($request->boolean('clear_auto_before', true)) {
            $request->merge(['source' => 'auto', 'clean_permissions' => true, 'clean_protections' => true]);
            $this->cleanup($request);
        }

        return [
            'permissions' => $setup->generateDefaultPermissions($empresaId, 'auto'),
            'protections' => $setup->generateSafeProtectionDrafts($empresaId, 'auto'),
        ];
    }

    private function resolveEmpresaId(Request $request, SecurityFeatureService $feature, bool $isSuper): ?int
    {
        if ($isSuper && $request->filled('empresa_id')) {
            return (int) $request->empresa_id;
        }

        return $feature->currentEmpresaId();
    }

    private function resolveScope(Request $request): array
    {
        $type = $request->input('scope_type', 'geral');
        $perfilId = $type === 'perfil' && $request->filled('perfil_acesso_id') ? (int) $request->perfil_acesso_id : null;
        $usuarioId = $type === 'usuario' && $request->filled('usuario_id') ? (int) $request->usuario_id : null;

        if (!$perfilId && !$usuarioId) {
            $type = 'geral';
        }

        return [
            'scope_type' => $type,
            'perfil_acesso_id' => $perfilId,
            'usuario_id' => $usuarioId,
        ];
    }

    private function resources(Request $request, bool $isSuper): Collection
    {
        return SecurityCrudResource::query()
            ->where('enabled', true)
            ->when(!$isSuper, fn ($q) => $q->where('tenant_visible', true)->where('super_admin_only', false))
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('resource_id'), fn ($q) => $q->where('id', (int) $request->resource_id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . trim($request->q) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('display_name', 'like', $term)
                        ->orWhere('plural_display_name', 'like', $term)
                        ->orWhere('model_class', 'like', $term)
                        ->orWhere('module', 'like', $term);
                });
            })
            ->orderBy('module')
            ->orderBy('display_name')
            ->limit(300)
            ->get();
    }

    private function permissions(?int $empresaId, array $scope, array $resourceIds): Collection
    {
        return SecurityCrudPermission::query()
            ->whereIn('security_crud_resource_id', $resourceIds ?: [0])
            ->where(function ($q) use ($empresaId) {
                $q->whereNull('empresa_id');
                if ($empresaId) {
                    $q->orWhere('empresa_id', $empresaId);
                }
            })
            ->when($scope['scope_type'] === 'perfil', fn ($q) => $q->where('perfil_acesso_id', $scope['perfil_acesso_id'])->whereNull('usuario_id'))
            ->when($scope['scope_type'] === 'usuario', fn ($q) => $q->where('usuario_id', $scope['usuario_id']))
            ->when($scope['scope_type'] === 'geral', fn ($q) => $q->whereNull('perfil_acesso_id')->whereNull('usuario_id'))
            ->orderByRaw('CASE WHEN empresa_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->get()
            ->keyBy('security_crud_resource_id');
    }

    private function protections(?int $empresaId, array $resourceIds): Collection
    {
        return SecurityCrudProtectionRule::query()
            ->whereIn('security_crud_resource_id', $resourceIds ?: [0])
            ->where(function ($q) use ($empresaId) {
                $q->whereNull('empresa_id');
                if ($empresaId) {
                    $q->orWhere('empresa_id', $empresaId);
                }
            })
            ->orderByRaw('CASE WHEN empresa_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->get()
            ->groupBy('security_crud_resource_id')
            ->map(fn ($items) => $items->keyBy('action'));
    }

    private function matrix(Collection $resources, Collection $permissions, Collection $protections): Collection
    {
        $permissionService = app(SecurityCrudPermissionService::class);

        return $resources->map(function (SecurityCrudResource $resource) use ($permissions, $protections, $permissionService) {
            $permission = $permissions->get($resource->id);
            $defaults = $permissionService->defaultPermissionsForResource($resource);
            $resourceProtections = $protections->get($resource->id, collect());

            $actions = [];
            foreach (SecurityCrudPermissionService::ACTIONS as $action => $label) {
                $column = 'can_' . $action;
                $can = $permission ? (bool) $permission->{$column} : (bool) ($defaults[$column] ?? true);
                $protection = $resourceProtections->get($action);
                $actions[$action] = [
                    'label' => $label,
                    'can' => $can,
                    'protection' => $protection,
                    'protection_type' => optional($protection)->protection_type ?: 'none',
                    'protection_enabled' => (bool) optional($protection)->enabled,
                ];
            }

            return [
                'resource' => $resource,
                'permission' => $permission,
                'actions' => $actions,
                'protected_count' => collect($actions)->filter(fn ($item) => $item['protection_enabled'] && $item['protection_type'] !== 'none')->count(),
                'denied_count' => collect($actions)->filter(fn ($item) => !$item['can'])->count(),
            ];
        });
    }

    private function usuarios(?int $empresaId): Collection
    {
        return Usuario::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->limit(500)
            ->get(['id', 'nome', 'login', 'empresa_id']);
    }

    private function applyCommonCleanupFilters($query, ?int $empresaId, ?int $resourceId, string $source): void
    {
        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        } else {
            $query->whereNull('empresa_id');
        }

        if ($resourceId) {
            $query->where('security_crud_resource_id', $resourceId);
        }

        if ($source === 'auto') {
            $query->whereIn('source', ['auto', 'setup', 'default']);
        } elseif ($source === 'manual') {
            $query->where('source', 'manual');
        }
    }

    private function assertTenantScope(?int $ruleEmpresaId, Request $request): void
    {
        $feature = app(SecurityFeatureService::class);
        if ($feature->isSuperAdmin()) {
            return;
        }

        $empresaId = $feature->currentEmpresaId();
        if ((int) $ruleEmpresaId !== (int) $empresaId) {
            abort(403, 'Registro de segurança fora do escopo da empresa atual.');
        }
    }
}
