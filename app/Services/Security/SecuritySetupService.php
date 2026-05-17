<?php

namespace App\Services\Security;

use App\Models\Security\EmpresaSecuritySetting;
use App\Models\Security\SecurityAuditPolicy;
use App\Models\Security\SecurityAuthorizer;
use App\Models\Security\SecurityAuthorizerToken;
use App\Models\Security\SecurityCrudPermission;
use App\Models\Security\SecurityCrudProtectionRule;
use App\Models\Security\SecurityCrudResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SecuritySetupService
{
    public function __construct(
        protected SecurityFeatureService $featureService,
        protected SecurityCrudResourceScannerService $scannerService,
        protected SecurityCrudPermissionService $permissionService,
        protected SecurityCrudProtectionService $protectionService
    ) {}

    public function checklist(?int $empresaId = null): array
    {
        $empresaId = $empresaId ?? $this->featureService->currentEmpresaId();
        $setting = $this->featureService->getOrCreateSetting($empresaId);
        $resources = $this->tenantResources();

        $resourcesCount = $resources->count();
        $permissionsCount = $this->tenantPermissionsQuery($empresaId)->count();
        $protectionsCount = $this->tenantProtectionsQuery($empresaId)->count();
        $authorizersCount = SecurityAuthorizer::query()->where('empresa_id', $empresaId)->where('enabled', true)->count();
        $activeTokensCount = $this->activeTokensQuery($empresaId)->count();
        $authorizersWithOtpCount = $this->authorizersWithOtpQuery($empresaId)->count();
        $authorizersWithoutCredential = $this->authorizersWithoutCredential($empresaId)->count();
        $auditPoliciesCount = SecurityAuditPolicy::query()->where('empresa_id', $empresaId)->where('enabled', true)->count();
        $authorizersWithoutToken = $this->authorizersWithoutToken($empresaId)->count();
        $criticalResourcesWithoutProtection = $this->criticalResourcesWithoutProtection($empresaId, $resources)->count();

        $steps = [
            'enabled' => [
                'label' => 'Segurança habilitada para o tenant',
                'done' => (bool) $setting->tenant_enabled,
                'description' => 'A empresa aceitou iniciar a parametrização. Nenhum CRUD é bloqueado apenas por esta etapa.',
            ],
            'resources' => [
                'label' => 'Recursos do sistema sincronizados',
                'done' => $resourcesCount > 0,
                'description' => 'Models herdadas de BaseModel foram detectadas e cadastradas como recursos de segurança.',
            ],
            'permissions' => [
                'label' => 'Permissões CRUD iniciais criadas',
                'done' => $resourcesCount > 0 && $permissionsCount >= $resourcesCount,
                'description' => 'Cria permissões padrão por empresa mantendo tudo liberado conforme os defaults das Models.',
            ],
            'authorizers' => [
                'label' => 'Autorizadores configurados',
                'done' => $authorizersCount > 0,
                'description' => 'Pelo menos um usuário autorizador ativo precisa existir antes de aplicar proteções com token/OTP.',
            ],
            'google_auth' => [
                'label' => 'Google Authenticator recomendado no setup',
                'done' => !$setting->google_auth_required || ($authorizersCount > 0 && $authorizersWithOtpCount > 0),
                'description' => 'O método recomendado para novas implantações é configurar app autenticador em pelo menos um autorizador. Tokens operacionais continuam disponíveis como complemento.',
            ],
            'tokens' => [
                'label' => 'Credenciais ativas para autorizadores',
                'done' => $authorizersCount > 0 && $authorizersWithoutCredential === 0,
                'description' => 'Todo autorizador ativo deve possuir Google Authenticator confirmado ou ao menos um token operacional válido.',
            ],
            'audit_policies' => [
                'label' => 'Políticas de auditoria criadas',
                'done' => $resourcesCount > 0 && $auditPoliciesCount >= $resourcesCount,
                'description' => 'Define visibilidade, JSON sensível e restauração por Model para o tenant.',
            ],
            'protections_review' => [
                'label' => 'Proteções críticas revisadas',
                'done' => $criticalResourcesWithoutProtection === 0,
                'description' => 'Recursos sensíveis ou com exclusão/restauração devem possuir proteção explícita quando o enforcement for ativado.',
            ],
            'setup_completed' => [
                'label' => 'Configuração inicial concluída',
                'done' => !empty($setting->setup_completed_at),
                'description' => 'Marco administrativo confirmando que o tenant revisou a parametrização inicial.',
            ],
            'enforcement' => [
                'label' => 'Aplicação das regras ativa',
                'done' => (bool) $setting->enforcement_enabled,
                'description' => 'Somente aqui o sistema passa a bloquear operações conforme permissões e proteções configuradas.',
            ],
        ];

        $requiredKeys = ['enabled', 'resources', 'permissions', 'authorizers', 'google_auth', 'tokens', 'audit_policies'];
        $canCompleteSetup = collect($requiredKeys)->every(fn ($key) => !empty($steps[$key]['done']));
        $canEnableEnforcement = $canCompleteSetup && !empty($setting->setup_completed_at);
        $canDisableLegacy = $canEnableEnforcement && (bool) $setting->enforcement_enabled;

        return [
            'setting' => $setting,
            'empresa_id' => $empresaId,
            'resources_count' => $resourcesCount,
            'permissions_count' => $permissionsCount,
            'protections_count' => $protectionsCount,
            'authorizers_count' => $authorizersCount,
            'active_tokens_count' => $activeTokensCount,
            'authorizers_with_otp_count' => $authorizersWithOtpCount,
            'authorizers_without_credential' => $authorizersWithoutCredential,
            'audit_policies_count' => $auditPoliciesCount,
            'authorizers_without_token' => $authorizersWithoutToken,
            'critical_resources_without_protection' => $criticalResourcesWithoutProtection,
            'steps' => $steps,
            'can_complete_setup' => $canCompleteSetup,
            'can_enable_enforcement' => $canEnableEnforcement,
            'can_disable_legacy' => $canDisableLegacy,
            'completion_percent' => $this->completionPercent($steps),
            'warnings' => $this->warnings($setting, $steps, $authorizersWithoutToken, $authorizersWithoutCredential, $authorizersWithOtpCount, $criticalResourcesWithoutProtection),
        ];
    }

    public function enableTenant(?int $empresaId = null, ?int $userId = null): EmpresaSecuritySetting
    {
        return $this->featureService->enableForTenant($empresaId, $userId);
    }

    public function syncResources(): array
    {
        return $this->scannerService->sync();
    }

    public function generateDefaultPermissions(?int $empresaId = null): int
    {
        $empresaId = $empresaId ?? $this->featureService->currentEmpresaId();
        $count = 0;
        foreach ($this->tenantResources() as $resource) {
            $defaults = $this->permissionService->defaultPermissionsForResource($resource);

            SecurityCrudPermission::updateOrCreate(
                [
                    'empresa_id' => $empresaId,
                    'security_crud_resource_id' => $resource->id,
                    'perfil_acesso_id' => null,
                    'usuario_id' => null,
                ],
                array_merge($defaults, ['enabled' => true])
            );

            $count++;
        }

        return $count;
    }

    public function generateDefaultAuditPolicies(?int $empresaId = null): int
    {
        $empresaId = $empresaId ?? $this->featureService->currentEmpresaId();
        $count = 0;
        foreach ($this->tenantResources() as $resource) {
            SecurityAuditPolicy::updateOrCreate(
                [
                    'empresa_id' => $empresaId,
                    'security_crud_resource_id' => $resource->id,
                ],
                [
                    'model_class' => $resource->model_class,
                    'tenant_can_view' => !$resource->super_admin_only && $resource->tenant_visible,
                    'tenant_can_view_json' => false,
                    'tenant_can_export_json' => false,
                    'tenant_can_restore' => false,
                    'super_admin_only' => (bool) $resource->super_admin_only,
                    'sanitize_fields' => $resource->sensitive ? $this->defaultSensitiveFields() : [],
                    'enabled' => true,
                ]
            );

            $count++;
        }

        return $count;
    }

    public function generateSafeProtectionDrafts(?int $empresaId = null): int
    {
        $empresaId = $empresaId ?? $this->featureService->currentEmpresaId();
        $count = 0;
        $setting = $this->featureService->getOrCreateSetting($empresaId);
        $defaultProtection = $setting->google_auth_required ? 'authorizer_otp' : 'authorizer_token_or_otp';

        foreach ($this->tenantResources() as $resource) {
            $actions = is_array($resource->actions) ? $resource->actions : [];
            $deleteEnabled = (bool) ($actions['delete'] ?? true);
            $restoreEnabled = (bool) ($actions['restore'] ?? false);
            $editEnabled = (bool) ($actions['edit'] ?? true);

            if ($resource->sensitive && $editEnabled) {
                $this->upsertProtection($empresaId, $resource->id, 'edit', $defaultProtection, 'Edição de recurso sensível exige liberação por autorizador.');
                $count++;
            }

            if ($deleteEnabled) {
                $this->upsertProtection($empresaId, $resource->id, 'delete', $defaultProtection, 'Exclusão exige liberação por autorizador.');
                $count++;
            }

            if ($restoreEnabled) {
                $this->upsertProtection($empresaId, $resource->id, 'restore', $defaultProtection, 'Restauração exige liberação por autorizador.');
                $count++;
            }
        }

        return $count;
    }

    public function markSetupCompleted(?int $empresaId = null): EmpresaSecuritySetting
    {
        $empresaId = $empresaId ?? $this->featureService->currentEmpresaId();
        $checklist = $this->checklist($empresaId);

        if (!$checklist['can_complete_setup']) {
            throw new \RuntimeException('Ainda existem etapas obrigatórias pendentes antes de concluir a configuração inicial.');
        }

        $setting = $this->featureService->getOrCreateSetting($empresaId);
        $setting->setup_completed_at = $setting->setup_completed_at ?: now();
        $setting->save();

        return $setting;
    }

    public function enableEnforcement(?int $empresaId = null): EmpresaSecuritySetting
    {
        $empresaId = $empresaId ?? $this->featureService->currentEmpresaId();
        $checklist = $this->checklist($empresaId);

        if (!$checklist['can_enable_enforcement']) {
            throw new \RuntimeException('Conclua a configuração inicial antes de aplicar as regras nos CRUDs.');
        }

        $setting = $this->featureService->getOrCreateSetting($empresaId);
        $setting->tenant_enabled = true;
        $setting->enforcement_enabled = true;
        $setting->save();

        return $setting;
    }

    public function disableLegacyPassword(?int $empresaId = null): EmpresaSecuritySetting
    {
        $empresaId = $empresaId ?? $this->featureService->currentEmpresaId();
        $checklist = $this->checklist($empresaId);

        if (!$checklist['can_disable_legacy']) {
            throw new \RuntimeException('A senha legada só pode ser desabilitada após ativar a aplicação das regras.');
        }

        $setting = $this->featureService->getOrCreateSetting($empresaId);
        $setting->legacy_password_disabled = true;
        $setting->save();

        return $setting;
    }

    protected function tenantResources(): Collection
    {
        return SecurityCrudResource::query()
            ->where('enabled', true)
            ->where('tenant_visible', true)
            ->where('super_admin_only', false)
            ->orderBy('module')
            ->orderBy('plural_display_name')
            ->get();
    }

    protected function tenantPermissionsQuery(?int $empresaId)
    {
        return SecurityCrudPermission::query()
            ->where('empresa_id', $empresaId)
            ->whereNull('perfil_acesso_id')
            ->whereNull('usuario_id')
            ->where('enabled', true);
    }

    protected function tenantProtectionsQuery(?int $empresaId)
    {
        return SecurityCrudProtectionRule::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true);
    }

    protected function activeTokensQuery(?int $empresaId)
    {
        return SecurityAuthorizerToken::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    protected function authorizersWithOtpQuery(?int $empresaId)
    {
        return SecurityAuthorizer::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->where('operation_otp_enabled', true)
            ->whereNotNull('operation_otp_confirmed_at');
    }

    protected function authorizersWithoutCredential(?int $empresaId): Collection
    {
        return SecurityAuthorizer::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('operation_otp_enabled', false)
                        ->orWhereNull('operation_otp_confirmed_at');
                })
                ->whereDoesntHave('tokens', function ($tokenQuery) {
                    $tokenQuery->where('enabled', true)
                        ->where(function ($q) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                        });
                });
            })
            ->get();
    }

    protected function authorizersWithoutToken(?int $empresaId): Collection
    {
        return SecurityAuthorizer::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->whereDoesntHave('tokens', function ($query) {
                $query->where('enabled', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                    });
            })
            ->get();
    }

    protected function criticalResourcesWithoutProtection(?int $empresaId, Collection $resources): Collection
    {
        $resourceIdsWithProtection = SecurityCrudProtectionRule::query()
            ->where('empresa_id', $empresaId)
            ->where('enabled', true)
            ->whereIn('action', ['edit', 'delete', 'restore'])
            ->pluck('security_crud_resource_id')
            ->unique()
            ->values();

        return $resources->filter(function (SecurityCrudResource $resource) use ($resourceIdsWithProtection) {
            $actions = is_array($resource->actions) ? $resource->actions : [];
            $isCritical = (bool) $resource->sensitive
                || (bool) ($actions['delete'] ?? true)
                || (bool) ($actions['restore'] ?? false);

            return $isCritical && !$resourceIdsWithProtection->contains($resource->id);
        })->values();
    }

    protected function upsertProtection(?int $empresaId, int $resourceId, string $action, string $type, string $message): void
    {
        SecurityCrudProtectionRule::updateOrCreate(
            [
                'empresa_id' => $empresaId,
                'security_crud_resource_id' => $resourceId,
                'action' => $action,
            ],
            [
                'protection_type' => $type,
                'requires_authorizer' => true,
                'allow_self_authorization' => false,
                'bypass_super_admin' => true,
                'bypass_company_admin' => false,
                'enabled' => true,
                'message' => $message,
            ]
        );
    }

    protected function defaultSensitiveFields(): array
    {
        return [
            'senha', 'password', 'senha_remover', 'token', 'token_sync', 'secret', 'client_secret',
            'access_token', 'refresh_token', 'otp_secret', 'csc', 'certificado', 'certificate',
        ];
    }

    protected function completionPercent(array $steps): int
    {
        if (empty($steps)) {
            return 0;
        }

        $done = collect($steps)->where('done', true)->count();
        return (int) round(($done / count($steps)) * 100);
    }

    protected function warnings(EmpresaSecuritySetting $setting, array $steps, int $authorizersWithoutToken, int $authorizersWithoutCredential, int $authorizersWithOtpCount, int $criticalResourcesWithoutProtection): array
    {
        $warnings = [];

        if (!$setting->tenant_enabled) {
            $warnings[] = 'A Segurança de Operações ainda está desabilitada para esta empresa.';
        }

        if (!empty($setting->enforcement_enabled) && empty($setting->setup_completed_at)) {
            $warnings[] = 'A aplicação das regras está ativa, mas a configuração inicial não foi marcada como concluída.';
        }

        if ($setting->google_auth_required && $authorizersWithOtpCount === 0) {
            $warnings[] = 'Google Authenticator está recomendado no setup, mas nenhum autorizador possui app autenticador confirmado.';
        }

        if ($authorizersWithoutCredential > 0) {
            $warnings[] = 'Existem autorizadores ativos sem Google Authenticator confirmado e sem token operacional válido.';
        }

        if ($criticalResourcesWithoutProtection > 0) {
            $warnings[] = 'Existem recursos críticos sem proteção explícita para edição, exclusão ou restauração.';
        }

        if (!empty($setting->legacy_password_disabled) && empty($setting->enforcement_enabled)) {
            $warnings[] = 'A senha legada está marcada como desabilitada, mas o enforcement ainda não está ativo.';
        }

        return $warnings;
    }
}
