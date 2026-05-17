<?php

namespace App\Services\Security;

use App\Models\Empresa;
use App\Models\Log;
use App\Models\Security\EmpresaSecuritySetting;
use App\Models\Security\SecurityAuthorizer;
use App\Models\Security\SecurityAuthorizerToken;
use App\Models\Security\SecurityCrudPermission;
use App\Models\Security\SecurityCrudProtectionRule;
use App\Models\Security\SecurityCrudResource;
use App\Models\Security\SecurityOperationAuthorization;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SecurityAdminCenterService
{
    public function dashboard(Request $request): array
    {
        $feature = app(SecurityFeatureService::class);
        $migrationState = app(SecurityMigrationStateService::class);

        $isSuper = $feature->isSuperAdmin();
        $empresaId = $this->resolveEmpresaId($request, $feature, $isSuper);
        $setting = $feature->getOrCreateSetting($empresaId);
        $migrationsReady = $migrationState->isReady();

        if (!$migrationsReady) {
            return $this->emptyPayload($setting, $isSuper, $empresaId, $migrationState->missingTables());
        }

        $resources = $this->resources($isSuper);
        $rules = $this->rules($isSuper, $empresaId);
        $permissions = $this->permissions($isSuper, $empresaId);
        $authorizers = $this->authorizers($empresaId);
        $tokens = $this->tokens($empresaId);
        $operations = $this->operations($empresaId, $isSuper);
        $logs = $this->logs($request, $isSuper, $empresaId);
        $empresas = $isSuper ? Empresa::query()->orderBy('nome')->get(['id', 'nome', 'cnpj']) : collect();

        return [
            'title' => 'Administração de Segurança',
            'isSuper' => $isSuper,
            'empresaId' => $empresaId,
            'empresas' => $empresas,
            'setting' => $setting,
            'migrationsReady' => true,
            'missingTables' => [],
            'summary' => $this->summary($setting, $resources, $rules, $permissions, $authorizers, $tokens, $operations, $logs),
            'resources' => $resources,
            'resourcesByModule' => $this->resourcesByModule($resources, $rules, $permissions),
            'rules' => $rules,
            'permissions' => $permissions,
            'authorizers' => $authorizers,
            'tokens' => $tokens,
            'operations' => $operations,
            'logs' => $logs,
            'usuarios' => $this->usuarios($empresaId),
            'actions' => SecurityCrudPermissionService::ACTIONS,
            'protectionTypes' => SecurityCrudProtectionService::PROTECTION_TYPES,
            'tips' => $this->tips($setting, $rules, $permissions, $authorizers, $tokens),
        ];
    }

    private function resolveEmpresaId(Request $request, SecurityFeatureService $feature, bool $isSuper): ?int
    {
        if ($isSuper && $request->filled('empresa_id')) {
            return (int) $request->empresa_id;
        }

        return $feature->currentEmpresaId();
    }

    private function emptyPayload(EmpresaSecuritySetting $setting, bool $isSuper, ?int $empresaId, array $missingTables): array
    {
        return [
            'title' => 'Administração de Segurança',
            'isSuper' => $isSuper,
            'empresaId' => $empresaId,
            'empresas' => collect(),
            'setting' => $setting,
            'migrationsReady' => false,
            'missingTables' => $missingTables,
            'summary' => [
                'resources_total' => 0,
                'rules_total' => 0,
                'permissions_total' => 0,
                'authorizers_total' => 0,
                'tokens_total' => 0,
                'logs_total' => 0,
                'protected_modules' => 0,
                'auth_method' => 'Não disponível',
                'status_label' => 'Migrations pendentes',
                'status_class' => 'danger',
            ],
            'resources' => collect(),
            'resourcesByModule' => collect(),
            'rules' => collect(),
            'permissions' => collect(),
            'authorizers' => collect(),
            'tokens' => collect(),
            'operations' => collect(),
            'logs' => collect(),
            'usuarios' => collect(),
            'actions' => SecurityCrudPermissionService::ACTIONS,
            'protectionTypes' => SecurityCrudProtectionService::PROTECTION_TYPES,
            'tips' => ['Execute as migrations antes de habilitar ou administrar a Segurança de Operações.'],
        ];
    }

    private function resources(bool $isSuper): Collection
    {
        return SecurityCrudResource::query()
            ->where('enabled', true)
            ->when(!$isSuper, fn ($q) => $q->where('tenant_visible', true)->where('super_admin_only', false))
            ->orderBy('module')
            ->orderBy('display_name')
            ->get();
    }

    private function rules(bool $isSuper, ?int $empresaId): Collection
    {
        return SecurityCrudProtectionRule::query()
            ->with(['resource', 'empresa'])
            ->when(!$isSuper, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($isSuper && $empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderByDesc('enabled')
            ->orderBy('action')
            ->limit(200)
            ->get();
    }

    private function permissions(bool $isSuper, ?int $empresaId): Collection
    {
        return SecurityCrudPermission::query()
            ->with(['resource', 'perfil', 'usuario', 'empresa'])
            ->when(!$isSuper, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($isSuper && $empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderByDesc('enabled')
            ->limit(200)
            ->get();
    }

    private function authorizers(?int $empresaId): Collection
    {
        return SecurityAuthorizer::query()
            ->with(['usuario', 'tokens'])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderByDesc('enabled')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    private function tokens(?int $empresaId): Collection
    {
        return SecurityAuthorizerToken::query()
            ->with(['authorizer.usuario'])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderByDesc('enabled')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    private function operations(?int $empresaId, bool $isSuper): Collection
    {
        return SecurityOperationAuthorization::query()
            ->with(['resource', 'executor', 'authorizer'])
            ->when(!$isSuper || $empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    private function logs(Request $request, bool $isSuper, ?int $empresaId): Collection
    {
        return Log::query()
            ->with(['usuario', 'filial'])
            ->when(!$isSuper || $empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($request->filled('audit_acao'), fn ($q) => $q->where('acao', $request->audit_acao))
            ->when($request->filled('audit_modelo'), fn ($q) => $q->whereRaw('INSTR(LOWER(modelo), ?) > 0', [strtolower(trim($request->audit_modelo))]))
            ->when($request->filled('audit_data_inicial'), fn ($q) => $q->whereDate('created_at', '>=', $request->audit_data_inicial))
            ->when($request->filled('audit_data_final'), fn ($q) => $q->whereDate('created_at', '<=', $request->audit_data_final))
            ->orderByDesc('id')
            ->limit(80)
            ->get();
    }

    private function usuarios(?int $empresaId): Collection
    {
        return Usuario::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->limit(200)
            ->get(['id', 'nome', 'login', 'empresa_id']);
    }

    private function summary(EmpresaSecuritySetting $setting, Collection $resources, Collection $rules, Collection $permissions, Collection $authorizers, Collection $tokens, Collection $operations, Collection $logs): array
    {
        $protectedModules = $rules->filter(fn ($rule) => (bool) $rule->enabled && $rule->protection_type !== 'none')
            ->map(fn ($rule) => optional($rule->resource)->module)
            ->filter()
            ->unique()
            ->count();

        $status = $this->status($setting);

        return [
            'resources_total' => $resources->count(),
            'rules_total' => $rules->count(),
            'permissions_total' => $permissions->count(),
            'authorizers_total' => $authorizers->where('enabled', true)->count(),
            'tokens_total' => $tokens->where('enabled', true)->count(),
            'logs_total' => $logs->count(),
            'protected_modules' => $protectedModules,
            'auth_method' => $setting->google_auth_required ? 'Google Authenticator recomendado' : 'Token ou app autenticador',
            'status_label' => $status['label'],
            'status_class' => $status['class'],
        ];
    }

    private function resourcesByModule(Collection $resources, Collection $rules, Collection $permissions): Collection
    {
        return $resources->groupBy('module')->map(function ($items, $module) use ($rules, $permissions) {
            $resourceIds = $items->pluck('id')->all();
            $moduleRules = $rules->whereIn('security_crud_resource_id', $resourceIds);
            $modulePermissions = $permissions->whereIn('security_crud_resource_id', $resourceIds);

            return [
                'module' => $module ?: 'Geral',
                'resources' => $items,
                'resources_count' => $items->count(),
                'rules_count' => $moduleRules->count(),
                'permissions_count' => $modulePermissions->count(),
                'protected_actions' => $moduleRules->where('enabled', true)->whereNotIn('protection_type', ['none'])->count(),
                'sensitive_count' => $items->where('sensitive', true)->count(),
            ];
        })->sortKeys();
    }

    private function status(EmpresaSecuritySetting $setting): array
    {
        if (!$setting->tenant_enabled) {
            return ['label' => 'Desabilitado', 'class' => 'secondary'];
        }

        if (!$setting->enforcement_enabled) {
            return ['label' => 'Configuração', 'class' => 'warning'];
        }

        return ['label' => 'Aplicando regras', 'class' => 'success'];
    }

    private function tips(EmpresaSecuritySetting $setting, Collection $rules, Collection $permissions, Collection $authorizers, Collection $tokens): array
    {
        $tips = [];

        if (!$setting->tenant_enabled) {
            $tips[] = 'A Segurança de Operações ainda não está habilitada para esta empresa. O sistema continua usando o fluxo atual.';
        }

        if ($setting->tenant_enabled && !$setting->enforcement_enabled) {
            $tips[] = 'O módulo está em modo configuração. Você pode cadastrar regras sem bloquear os usuários.';
        }

        if ($setting->enforcement_enabled && $permissions->isEmpty()) {
            $tips[] = 'Com enforcement ativo, cadastre permissões CRUD para evitar comportamento permissivo não desejado.';
        }

        if ($setting->enforcement_enabled && $rules->whereNotIn('protection_type', ['none'])->isEmpty()) {
            $tips[] = 'Nenhuma ação crítica possui proteção configurada. Revise edição, exclusão e restauração.';
        }

        if ($authorizers->where('enabled', true)->isNotEmpty() && $tokens->where('enabled', true)->isEmpty()) {
            $tips[] = 'Existem autorizadores ativos sem token operacional ativo. Configure App Auth ou token complementar.';
        }

        if (empty($tips)) {
            $tips[] = 'Configuração sem pendências críticas aparentes. Revise a auditoria periodicamente.';
        }

        return $tips;
    }
}
