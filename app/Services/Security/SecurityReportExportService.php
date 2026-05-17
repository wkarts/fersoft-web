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
use Illuminate\Support\Facades\Schema;

class SecurityReportExportService
{
    private SecurityFeatureService $featureService;
    private SecurityHealthService $healthService;

    public function __construct(SecurityFeatureService $featureService, SecurityHealthService $healthService)
    {
        $this->featureService = $featureService;
        $this->healthService = $healthService;
    }

    public function availableReports(bool $superAdmin): array
    {
        $reports = [
            'health' => 'Diagnóstico da Segurança',
            'resources' => 'Recursos do Sistema',
            'permissions' => 'Permissões CRUD',
            'protections' => 'Proteções de Operação',
            'authorizers' => 'Autorizadores',
            'tokens' => 'Tokens de Liberação',
            'audit-policies' => 'Políticas de Auditoria',
            'operations' => 'Autorizações Operacionais',
        ];

        if ($superAdmin) {
            $reports['companies'] = 'Empresas / Status de Implantação';
        }

        return $reports;
    }

    public function build(string $report, ?int $empresaId = null): array
    {
        if (!$this->featureService->migrationsReady()) {
            return [[
                'status' => 'migrations_pending',
                'message' => 'As migrations da Segurança de Operações ainda não foram executadas.',
                'missing_tables' => implode(', ', $this->featureService->missingMigrationTables()),
            ]];
        }

        switch ($report) {
            case 'health':
                return $this->healthRows($empresaId);
            case 'companies':
                return $this->companyRows();
            case 'resources':
                return $this->resourceRows();
            case 'permissions':
                return $this->permissionRows($empresaId);
            case 'protections':
                return $this->protectionRows($empresaId);
            case 'authorizers':
                return $this->authorizerRows($empresaId);
            case 'tokens':
                return $this->tokenRows($empresaId);
            case 'audit-policies':
                return $this->auditPolicyRows($empresaId);
            case 'operations':
                return $this->operationRows($empresaId);
            default:
                abort(404, 'Relatório de segurança não encontrado.');
        }
    }

    public function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'w+');

        if (count($rows) === 0) {
            fputcsv($handle, ['sem_registros'], ';');
        } else {
            $headers = array_keys($rows[0]);
            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, array_map([$this, 'normalizeCsvValue'], $row), ';');
            }
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF" . $content;
    }

    public function filename(string $report, string $format, ?int $empresaId = null): string
    {
        $suffix = $empresaId ? ('empresa-' . $empresaId) : 'global';
        return 'seguranca-' . $report . '-' . $suffix . '-' . date('Ymd-His') . '.' . $format;
    }

    private function healthRows(?int $empresaId): array
    {
        if ($empresaId) {
            $diagnostic = $this->healthService->company($empresaId);
            $row = $diagnostic['row'];

            return [[
                'empresa_id' => data_get($diagnostic, 'empresa.id'),
                'empresa_nome' => data_get($diagnostic, 'empresa.nome'),
                'tenant_enabled' => $this->boolLabel($row['tenant_enabled'] ?? false),
                'setup_completed' => $this->boolLabel($row['setup_completed'] ?? false),
                'enforcement_enabled' => $this->boolLabel($row['enforcement_enabled'] ?? false),
                'legacy_active' => $this->boolLabel($row['legacy_active'] ?? false),
                'health_level' => $row['health_level'] ?? '',
                'score' => $row['score'] ?? '',
                'permissions_count' => $row['permissions_count'] ?? 0,
                'protection_count' => $row['protection_count'] ?? 0,
                'authorizers_count' => $row['authorizers_count'] ?? 0,
                'active_tokens_count' => $row['active_tokens_count'] ?? 0,
                'audit_policy_count' => $row['audit_policy_count'] ?? 0,
                'issues' => implode(' | ', $row['issues'] ?? []),
                'warnings' => implode(' | ', $row['warnings'] ?? []),
            ]];
        }

        $diagnostic = $this->healthService->global();
        return collect($diagnostic['rows'] ?? [])->map(function ($row) {
            return [
                'empresa_id' => $row['empresa_id'] ?? '',
                'empresa_nome' => $row['empresa_nome'] ?? '',
                'tenant_enabled' => $this->boolLabel($row['tenant_enabled'] ?? false),
                'setup_completed' => $this->boolLabel($row['setup_completed'] ?? false),
                'enforcement_enabled' => $this->boolLabel($row['enforcement_enabled'] ?? false),
                'legacy_active' => $this->boolLabel($row['legacy_active'] ?? false),
                'health_level' => $row['health_level'] ?? '',
                'score' => $row['score'] ?? '',
                'permissions_count' => $row['permissions_count'] ?? 0,
                'protection_count' => $row['protection_count'] ?? 0,
                'authorizers_count' => $row['authorizers_count'] ?? 0,
                'active_tokens_count' => $row['active_tokens_count'] ?? 0,
                'audit_policy_count' => $row['audit_policy_count'] ?? 0,
                'issues' => implode(' | ', $row['issues'] ?? []),
                'warnings' => implode(' | ', $row['warnings'] ?? []),
            ];
        })->values()->all();
    }

    private function companyRows(): array
    {
        $settings = EmpresaSecuritySetting::query()->get()->keyBy('empresa_id');

        return Empresa::query()->orderBy('nome')->get()->map(function ($empresa) use ($settings) {
            $setting = $settings->get($empresa->id);

            return [
                'empresa_id' => $empresa->id,
                'empresa_nome' => $empresa->nome,
                'cnpj' => $empresa->cnpj ?? '',
                'tenant_enabled' => $this->boolLabel(optional($setting)->tenant_enabled),
                'enforcement_enabled' => $this->boolLabel(optional($setting)->enforcement_enabled),
                'legacy_password_disabled' => $this->boolLabel(optional($setting)->legacy_password_disabled),
                'google_auth_required' => $this->boolLabel(optional($setting)->google_auth_required),
                'audit_sensitive_export_enabled' => $this->boolLabel(optional($setting)->audit_sensitive_export_enabled),
                'restore_from_audit_enabled' => $this->boolLabel(optional($setting)->restore_from_audit_enabled),
                'setup_completed_at' => optional($setting)->setup_completed_at,
                'enabled_at' => optional($setting)->enabled_at,
                'enabled_by' => optional($setting)->enabled_by,
            ];
        })->values()->all();
    }

    private function resourceRows(): array
    {
        return SecurityCrudResource::query()
            ->orderBy('module')
            ->orderBy('plural_display_name')
            ->get()
            ->map(function ($resource) {
                return [
                    'id' => $resource->id,
                    'module' => $resource->module,
                    'name' => $resource->display_name,
                    'plural_name' => $resource->plural_display_name,
                    'model_class' => $resource->model_class,
                    'controller_class' => $resource->controller_class,
                    'route_prefix' => $resource->route_prefix,
                    'table_name' => $resource->table_name,
                    'sensitive' => $this->boolLabel($resource->sensitive),
                    'tenant_visible' => $this->boolLabel($resource->tenant_visible),
                    'super_admin_only' => $this->boolLabel($resource->super_admin_only),
                    'enabled' => $this->boolLabel($resource->enabled),
                    'source' => $resource->source,
                ];
            })->values()->all();
    }

    private function permissionRows(?int $empresaId): array
    {
        return SecurityCrudPermission::query()
            ->with(['empresa', 'resource', 'perfil', 'usuario'])
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('empresa_id')
            ->orderBy('security_crud_resource_id')
            ->get()
            ->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'empresa_id' => $permission->empresa_id,
                    'empresa_nome' => optional($permission->empresa)->nome,
                    'resource_id' => $permission->security_crud_resource_id,
                    'resource' => (optional($permission->resource)->plural_display_name ?: optional($permission->resource)->display_name),
                    'model_class' => optional($permission->resource)->model_class,
                    'perfil_id' => $permission->perfil_acesso_id,
                    'perfil' => optional($permission->perfil)->nome,
                    'usuario_id' => $permission->usuario_id,
                    'usuario' => optional($permission->usuario)->nome ?: optional($permission->usuario)->login,
                    'can_view' => $this->boolLabel($permission->can_view),
                    'can_create' => $this->boolLabel($permission->can_create),
                    'can_edit' => $this->boolLabel($permission->can_edit),
                    'can_delete' => $this->boolLabel($permission->can_delete),
                    'can_restore' => $this->boolLabel($permission->can_restore),
                    'can_export' => $this->boolLabel($permission->can_export),
                    'can_print' => $this->boolLabel($permission->can_print),
                    'enabled' => $this->boolLabel($permission->enabled),
                ];
            })->values()->all();
    }

    private function protectionRows(?int $empresaId): array
    {
        return SecurityCrudProtectionRule::query()
            ->with(['empresa', 'resource'])
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('empresa_id')
            ->orderBy('security_crud_resource_id')
            ->orderBy('action')
            ->get()
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'empresa_id' => $rule->empresa_id,
                    'empresa_nome' => optional($rule->empresa)->nome,
                    'resource_id' => $rule->security_crud_resource_id,
                    'resource' => (optional($rule->resource)->plural_display_name ?: optional($rule->resource)->display_name),
                    'model_class' => optional($rule->resource)->model_class,
                    'action' => $rule->action,
                    'protection_type' => $rule->protection_type,
                    'requires_authorizer' => $this->boolLabel($rule->requires_authorizer),
                    'allow_self_authorization' => $this->boolLabel($rule->allow_self_authorization),
                    'bypass_super_admin' => $this->boolLabel($rule->bypass_super_admin),
                    'bypass_company_admin' => $this->boolLabel($rule->bypass_company_admin),
                    'enabled' => $this->boolLabel($rule->enabled),
                    'message' => $rule->message,
                ];
            })->values()->all();
    }

    private function authorizerRows(?int $empresaId): array
    {
        return SecurityAuthorizer::query()
            ->with(['empresa', 'usuario'])
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('empresa_id')
            ->orderBy('usuario_id')
            ->get()
            ->map(function ($authorizer) {
                return [
                    'id' => $authorizer->id,
                    'empresa_id' => $authorizer->empresa_id,
                    'empresa_nome' => optional($authorizer->empresa)->nome,
                    'usuario_id' => $authorizer->usuario_id,
                    'usuario' => optional($authorizer->usuario)->nome ?: optional($authorizer->usuario)->login,
                    'enabled' => $this->boolLabel($authorizer->enabled),
                    'can_authorize_all_resources' => $this->boolLabel($authorizer->can_authorize_all_resources),
                    'can_authorize_view' => $this->boolLabel($authorizer->can_authorize_view),
                    'can_authorize_create' => $this->boolLabel($authorizer->can_authorize_create),
                    'can_authorize_edit' => $this->boolLabel($authorizer->can_authorize_edit),
                    'can_authorize_delete' => $this->boolLabel($authorizer->can_authorize_delete),
                    'can_authorize_restore' => $this->boolLabel($authorizer->can_authorize_restore),
                    'can_authorize_export' => $this->boolLabel($authorizer->can_authorize_export),
                    'can_authorize_print' => $this->boolLabel($authorizer->can_authorize_print),
                ];
            })->values()->all();
    }

    private function tokenRows(?int $empresaId): array
    {
        return SecurityAuthorizerToken::query()
            ->with(['empresa', 'authorizer.usuario'])
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('empresa_id')
            ->orderBy('security_authorizer_id')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'empresa_id' => $token->empresa_id,
                    'empresa_nome' => optional($token->empresa)->nome,
                    'authorizer_id' => $token->security_authorizer_id,
                    'authorizer_usuario' => optional(optional($token->authorizer)->usuario)->nome ?: optional(optional($token->authorizer)->usuario)->login,
                    'name' => $token->name,
                    'enabled' => $this->boolLabel($token->enabled),
                    'expires_at' => $token->expires_at,
                    'last_used_at' => $token->last_used_at,
                    'token_hash_exportado' => 'NÃO',
                ];
            })->values()->all();
    }

    private function auditPolicyRows(?int $empresaId): array
    {
        return SecurityAuditPolicy::query()
            ->with('empresa')
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where(function ($query) use ($empresaId) {
                    $query->where('empresa_id', $empresaId)->orWhereNull('empresa_id');
                });
            })
            ->orderBy('empresa_id')
            ->orderBy('model_class')
            ->get()
            ->map(function ($policy) {
                return [
                    'id' => $policy->id,
                    'empresa_id' => $policy->empresa_id,
                    'empresa_nome' => optional($policy->empresa)->nome ?: 'Global',
                    'model_class' => $policy->model_class,
                    'tenant_can_view' => $this->boolLabel($policy->tenant_can_view),
                    'tenant_can_view_json' => $this->boolLabel($policy->tenant_can_view_json),
                    'tenant_can_export_json' => $this->boolLabel($policy->tenant_can_export_json),
                    'tenant_can_restore' => $this->boolLabel($policy->tenant_can_restore),
                    'super_admin_only' => $this->boolLabel($policy->super_admin_only),
                    'enabled' => $this->boolLabel($policy->enabled),
                    'sanitize_fields' => is_array($policy->sanitize_fields) ? implode(', ', $policy->sanitize_fields) : $policy->sanitize_fields,
                ];
            })->values()->all();
    }

    private function operationRows(?int $empresaId): array
    {
        return SecurityOperationAuthorization::query()
            ->with(['empresa', 'resource', 'executor', 'authorizer'])
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderByDesc('id')
            ->limit(5000)
            ->get()
            ->map(function ($authorization) {
                return [
                    'id' => $authorization->id,
                    'empresa_id' => $authorization->empresa_id,
                    'empresa_nome' => optional($authorization->empresa)->nome,
                    'resource' => (optional($authorization->resource)->plural_display_name ?: optional($authorization->resource)->display_name),
                    'model_class' => optional($authorization->resource)->model_class,
                    'executor_user_id' => $authorization->executor_user_id,
                    'executor' => optional($authorization->executor)->nome ?: optional($authorization->executor)->login,
                    'authorizer_user_id' => $authorization->authorizer_user_id,
                    'authorizer' => optional($authorization->authorizer)->nome ?: optional($authorization->authorizer)->login,
                    'action' => $authorization->action,
                    'record_id' => $authorization->record_id,
                    'expires_at' => $authorization->expires_at,
                    'used_at' => $authorization->used_at,
                    'ip_address' => $authorization->ip_address,
                    'created_at' => $authorization->created_at,
                ];
            })->values()->all();
    }

    private function abortIfNotSuperAdmin(): void
    {
        if (!$this->featureService->isSuperAdmin()) {
            abort(403, 'Relatório disponível apenas para SuperAdmin.');
        }
    }

    private function boolLabel($value): string
    {
        return $value ? 'Sim' : 'Não';
    }

    private function normalizeCsvValue($value): string
    {
        if (is_bool($value)) {
            return $this->boolLabel($value);
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}
