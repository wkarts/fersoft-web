<?php

namespace App\Services\Security;

use App\Models\Empresa;
use App\Models\Security\EmpresaSecuritySetting;
use App\Models\Security\SecurityAuditPolicy;
use App\Models\Security\SecurityAuthorizer;
use App\Models\Security\SecurityAuthorizerToken;
use App\Models\Security\SecurityCrudPermission;
use App\Models\Security\SecurityCrudProtectionRule;
use App\Models\Security\SecurityCrudResource;
use App\Models\Security\SecurityOperationAuthorization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SecurityHealthService
{
    protected function migrationsReady(): bool
    {
        return app(SecurityMigrationStateService::class)->isReady();
    }

    protected function missingTables(): array
    {
        return app(SecurityMigrationStateService::class)->missingTables();
    }
    private const CRITICAL_ACTIONS = ['edit', 'delete', 'restore', 'export'];

    public function global(): array
    {
        if (!$this->migrationsReady()) {
            return [
                'summary' => [
                    'companies_total' => Empresa::query()->count(),
                    'tenant_enabled' => 0,
                    'setup_completed' => 0,
                    'enforcement_enabled' => 0,
                    'legacy_active' => 0,
                    'with_critical_issues' => 0,
                    'with_warnings' => 0,
                    'healthy' => 0,
                    'resources_total' => 0,
                    'resources_sensitive' => 0,
                    'resources_super_admin_only' => 0,
                    'global_audit_policies' => 0,
                    'migrations_ready' => false,
                    'missing_tables' => $this->missingTables(),
                ],
                'resources' => [
                    'total' => 0,
                    'tenant_visible' => 0,
                    'sensitive' => 0,
                    'super_admin_only' => 0,
                    'without_global_audit_policy' => collect(),
                ],
                'rows' => collect(),
            ];
        }

        $resources = SecurityCrudResource::query()->where('enabled', true)->get();
        $settings = EmpresaSecuritySetting::query()->get()->keyBy('empresa_id');
        $empresas = Empresa::query()->orderBy('nome')->get();

        $rows = $empresas->map(function ($empresa) use ($settings, $resources) {
            return $this->companyRow($empresa, $settings->get($empresa->id), $resources);
        });

        return [
            'summary' => [
                'companies_total' => $rows->count(),
                'tenant_enabled' => $rows->where('tenant_enabled', true)->count(),
                'setup_completed' => $rows->where('setup_completed', true)->count(),
                'enforcement_enabled' => $rows->where('enforcement_enabled', true)->count(),
                'legacy_active' => $rows->where('legacy_active', true)->count(),
                'with_critical_issues' => $rows->where('health_level', 'danger')->count(),
                'with_warnings' => $rows->where('health_level', 'warning')->count(),
                'healthy' => $rows->where('health_level', 'success')->count(),
                'resources_total' => $resources->count(),
                'resources_sensitive' => $resources->where('sensitive', true)->count(),
                'resources_super_admin_only' => $resources->where('super_admin_only', true)->count(),
                'global_audit_policies' => SecurityAuditPolicy::query()->whereNull('empresa_id')->where('enabled', true)->count(),
            ],
            'resources' => $this->resourceDiagnostics($resources),
            'rows' => $rows,
        ];
    }

    public function company(int $empresaId): array
    {
        $empresa = Empresa::findOrFail($empresaId);

        if (!$this->migrationsReady()) {
            $row = [
                'empresa' => $empresa,
                'setting' => null,
                'tenant_enabled' => false,
                'setup_completed' => false,
                'enforcement_enabled' => false,
                'legacy_active' => false,
                'visible_resources' => 0,
                'permissions_count' => 0,
                'protection_count' => 0,
                'protected_critical_rules' => 0,
                'authorizers_count' => 0,
                'active_tokens_count' => 0,
                'audit_policy_count' => 0,
                'issues' => ['Migrations da Segurança de Operações ainda não foram executadas.'],
                'warnings' => $this->missingTables(),
                'health_level' => 'danger',
                'score' => 0,
            ];

            return [
                'empresa' => $empresa,
                'setting' => null,
                'row' => $row,
                'resources' => ['visible_total' => 0, 'without_permissions' => collect(), 'without_protection' => collect()],
                'authorizers' => ['total' => 0, 'enabled' => 0, 'without_active_token' => collect()],
                'audit' => ['policies_total' => 0, 'without_policy' => collect(), 'restore_enabled_count' => 0, 'json_export_enabled_count' => 0],
                'operations' => ['total' => 0, 'used' => 0, 'pending' => 0, 'expired' => 0, 'last' => collect()],
                'recommendations' => ['Executar php artisan migrate antes de habilitar a Segurança de Operações.'],
            ];
        }
        $setting = EmpresaSecuritySetting::query()->where('empresa_id', $empresa->id)->first();
        $resources = SecurityCrudResource::query()->where('enabled', true)->get();
        $row = $this->companyRow($empresa, $setting, $resources);

        return [
            'empresa' => $empresa,
            'setting' => $setting,
            'row' => $row,
            'resources' => $this->companyResourceDiagnostics($empresa->id, $resources),
            'authorizers' => $this->authorizerDiagnostics($empresa->id),
            'audit' => $this->auditDiagnostics($empresa->id, $resources),
            'operations' => $this->operationDiagnostics($empresa->id),
            'recommendations' => $this->recommendations($row),
        ];
    }

    private function companyRow($empresa, ?EmpresaSecuritySetting $setting, Collection $resources): array
    {
        $empresaId = (int) $empresa->id;
        $tenantEnabled = (bool) optional($setting)->tenant_enabled;
        $enforcementEnabled = (bool) optional($setting)->enforcement_enabled;
        $setupCompleted = !empty(optional($setting)->setup_completed_at);
        $legacyActive = !(bool) optional($setting)->legacy_password_disabled;

        $visibleResources = $resources->filter(function ($resource) {
            return (bool) $resource->tenant_visible && !(bool) $resource->super_admin_only;
        });

        $permissionsCount = SecurityCrudPermission::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->count();

        $protectionCount = SecurityCrudProtectionRule::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->count();

        $protectedCriticalRules = SecurityCrudProtectionRule::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->whereIn('action', self::CRITICAL_ACTIONS)
            ->whereNotIn('protection_type', ['none'])
            ->count();

        $authorizersCount = SecurityAuthorizer::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->count();

        $activeTokensCount = SecurityAuthorizerToken::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->count();

        $auditPolicyCount = SecurityAuditPolicy::query()
            ->where(function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId)->orWhereNull('empresa_id');
            })
            ->where('enabled', true)
            ->count();

        $issues = [];
        $warnings = [];

        if (!$tenantEnabled) {
            $warnings[] = 'Segurança de Operações ainda não habilitada para o tenant.';
        }

        if ($tenantEnabled && !$setupCompleted) {
            $warnings[] = 'Assistente/configuração inicial ainda não concluído.';
        }

        if ($tenantEnabled && $enforcementEnabled && $permissionsCount === 0) {
            $issues[] = 'Aplicação das regras está ativa, mas não há permissões CRUD cadastradas.';
        }

        if ($tenantEnabled && $enforcementEnabled && $protectedCriticalRules === 0) {
            $issues[] = 'Aplicação das regras está ativa, mas não há proteções críticas configuradas.';
        }

        if ($tenantEnabled && $enforcementEnabled && $authorizersCount === 0) {
            $issues[] = 'Aplicação das regras está ativa, mas não há autorizadores ativos.';
        }

        if ($authorizersCount > 0 && $activeTokensCount === 0) {
            $issues[] = 'Existem autorizadores ativos, mas nenhum token ativo/válido.';
        }

        if ($legacyActive && $enforcementEnabled) {
            $warnings[] = 'Senha legada ainda está ativa mesmo com enforcement habilitado.';
        }

        if ($tenantEnabled && $auditPolicyCount === 0) {
            $warnings[] = 'Nenhuma política de auditoria encontrada para este tenant.';
        }

        $healthLevel = 'success';
        if (count($issues) > 0) {
            $healthLevel = 'danger';
        } elseif (count($warnings) > 0) {
            $healthLevel = 'warning';
        }

        return [
            'empresa' => $empresa,
            'setting' => $setting,
            'tenant_enabled' => $tenantEnabled,
            'setup_completed' => $setupCompleted,
            'enforcement_enabled' => $enforcementEnabled,
            'legacy_active' => $legacyActive,
            'visible_resources' => $visibleResources->count(),
            'permissions_count' => $permissionsCount,
            'protection_count' => $protectionCount,
            'protected_critical_rules' => $protectedCriticalRules,
            'authorizers_count' => $authorizersCount,
            'active_tokens_count' => $activeTokensCount,
            'audit_policy_count' => $auditPolicyCount,
            'issues' => $issues,
            'warnings' => $warnings,
            'health_level' => $healthLevel,
            'score' => $this->score($tenantEnabled, $setupCompleted, $enforcementEnabled, $legacyActive, $permissionsCount, $protectedCriticalRules, $authorizersCount, $activeTokensCount, $auditPolicyCount),
        ];
    }

    private function score(bool $tenantEnabled, bool $setupCompleted, bool $enforcementEnabled, bool $legacyActive, int $permissionsCount, int $protectedCriticalRules, int $authorizersCount, int $activeTokensCount, int $auditPolicyCount): int
    {
        $score = 0;
        if ($tenantEnabled) $score += 15;
        if ($setupCompleted) $score += 15;
        if ($enforcementEnabled) $score += 15;
        if (!$legacyActive) $score += 10;
        if ($permissionsCount > 0) $score += 15;
        if ($protectedCriticalRules > 0) $score += 15;
        if ($authorizersCount > 0 && $activeTokensCount > 0) $score += 10;
        if ($auditPolicyCount > 0) $score += 5;

        return min(100, $score);
    }

    private function resourceDiagnostics(Collection $resources): array
    {
        $globalAuditPolicies = SecurityAuditPolicy::query()
            ->whereNull('empresa_id')
            ->where('enabled', true)
            ->pluck('model_class')
            ->filter()
            ->unique()
            ->values();

        $resourcesWithoutGlobalAudit = $resources->filter(function ($resource) use ($globalAuditPolicies) {
            return !$globalAuditPolicies->contains($resource->model_class);
        })->values();

        return [
            'total' => $resources->count(),
            'tenant_visible' => $resources->where('tenant_visible', true)->count(),
            'sensitive' => $resources->where('sensitive', true)->count(),
            'super_admin_only' => $resources->where('super_admin_only', true)->count(),
            'without_global_audit_policy' => $resourcesWithoutGlobalAudit,
        ];
    }

    private function companyResourceDiagnostics(int $empresaId, Collection $resources): array
    {
        $visibleResources = $resources->filter(function ($resource) {
            return (bool) $resource->tenant_visible && !(bool) $resource->super_admin_only;
        })->values();

        $permissionsResourceIds = SecurityCrudPermission::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->pluck('security_crud_resource_id')
            ->unique();

        $protectionResourceIds = SecurityCrudProtectionRule::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->whereNotIn('protection_type', ['none'])
            ->pluck('security_crud_resource_id')
            ->unique();

        return [
            'visible_total' => $visibleResources->count(),
            'without_permissions' => $visibleResources->filter(function ($resource) use ($permissionsResourceIds) {
                return !$permissionsResourceIds->contains($resource->id);
            })->values(),
            'without_protection' => $visibleResources->filter(function ($resource) use ($protectionResourceIds) {
                return !$protectionResourceIds->contains($resource->id);
            })->values(),
        ];
    }

    private function authorizerDiagnostics(int $empresaId): array
    {
        $authorizers = SecurityAuthorizer::query()
            ->with(['usuario', 'tokens'])
            ->where('empresa_id', $empresaId)
            ->orderBy('id', 'desc')
            ->get();

        $withoutToken = $authorizers->filter(function (SecurityAuthorizer $authorizer) {
            return $authorizer->tokens->filter(function (SecurityAuthorizerToken $token) {
                return (bool) $token->enabled && (!$token->expires_at || $token->expires_at->gte(now()));
            })->count() === 0;
        })->values();

        return [
            'total' => $authorizers->count(),
            'enabled' => $authorizers->where('enabled', true)->count(),
            'without_active_token' => $withoutToken,
        ];
    }

    private function auditDiagnostics(int $empresaId, Collection $resources): array
    {
        $policies = SecurityAuditPolicy::query()
            ->where(function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId)->orWhereNull('empresa_id');
            })
            ->where('enabled', true)
            ->get();

        $policyModels = $policies->pluck('model_class')->filter()->unique();
        $visibleResources = $resources->filter(function ($resource) {
            return (bool) $resource->tenant_visible && !(bool) $resource->super_admin_only;
        });

        return [
            'policies_total' => $policies->count(),
            'without_policy' => $visibleResources->filter(function ($resource) use ($policyModels) {
                return !$policyModels->contains($resource->model_class);
            })->values(),
            'restore_enabled_count' => $policies->where('tenant_can_restore', true)->count(),
            'json_export_enabled_count' => $policies->where('tenant_can_export_json', true)->count(),
        ];
    }

    private function operationDiagnostics(int $empresaId): array
    {
        $query = SecurityOperationAuthorization::query()->where('empresa_id', $empresaId);

        return [
            'total' => (clone $query)->count(),
            'used' => (clone $query)->whereNotNull('used_at')->count(),
            'pending' => (clone $query)->whereNull('used_at')->where('expires_at', '>=', now())->count(),
            'expired' => (clone $query)->whereNull('used_at')->where('expires_at', '<', now())->count(),
            'last' => (clone $query)->orderBy('id', 'desc')->limit(10)->get(),
        ];
    }

    private function recommendations(array $row): array
    {
        $items = [];

        if (!$row['tenant_enabled']) {
            $items[] = 'Habilitar Segurança de Operações para iniciar a parametrização.';
        }

        if ($row['tenant_enabled'] && !$row['setup_completed']) {
            $items[] = 'Concluir a configuração inicial antes de ativar enforcement.';
        }

        if ($row['permissions_count'] === 0) {
            $items[] = 'Cadastrar permissões CRUD por perfil ou usuário antes de ativar enforcement.';
        }

        if ($row['protected_critical_rules'] === 0) {
            $items[] = 'Criar proteções para ações críticas como editar, excluir, restaurar e exportar.';
        }

        if ($row['authorizers_count'] === 0) {
            $items[] = 'Cadastrar ao menos um autorizador operacional.';
        }

        if ($row['authorizers_count'] > 0 && $row['active_tokens_count'] === 0) {
            $items[] = 'Gerar token ativo para os autorizadores cadastrados.';
        }

        if ($row['legacy_active'] && $row['enforcement_enabled']) {
            $items[] = 'Desativar senha legada após validar todas as regras novas.';
        }

        if ($row['audit_policy_count'] === 0) {
            $items[] = 'Criar políticas de auditoria para controlar JSON, exportação e restauração.';
        }

        return $items;
    }
}
