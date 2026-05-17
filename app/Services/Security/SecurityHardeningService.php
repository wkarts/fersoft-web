<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Schema;

class SecurityHardeningService
{
    public function __construct(protected SecurityMigrationStateService $migrationState) {}

    public function report(): array
    {
        $checks = [];

        $checks[] = $this->check(
            'security_migrations',
            $this->migrationState->isReady(),
            $this->migrationState->isReady()
                ? 'Todas as tabelas da Segurança de Operações existem.'
                : 'Existem tabelas pendentes: ' . implode(', ', $this->migrationState->missingTables())
        );

        $checks[] = $this->check(
            'base_model_metadata',
            method_exists(\App\Models\BaseModel::class, 'securityResource'),
            'BaseModel possui método securityResource() passivo.'
        );

        $checks[] = $this->check(
            'security_super_admin_middleware',
            class_exists(\App\Http\Middleware\EnsureSecuritySuperAdmin::class),
            'Middleware security.super_admin disponível para rotas administrativas.'
        );

        $checks[] = $this->check(
            'security_crud_middleware',
            class_exists(\App\Http\Middleware\CheckCrudSecurity::class),
            'Middleware security.crud disponível para controllers fora do BaseController.'
        );

        $checks[] = $this->check(
            'tenant_context_resolver',
            class_exists(\App\Services\Security\SecurityTenantContextService::class),
            'Resolvedor central de tenant disponível para operar mesmo quando as tabelas do CRUD não usam empresa_id.'
        );

        $checks[] = $this->check(
            'security_js_global',
            file_exists(public_path('js/security-operation.js')),
            'JavaScript global public/js/security-operation.js encontrado.'
        );

        $checks[] = $this->check(
            'security_modal_global',
            file_exists(resource_path('views/security/partials/operation_modal.blade.php')),
            'Modal global de autorização encontrado.'
        );

        $checks[] = $this->check(
            'resource_display_columns',
            !$this->migrationState->isReady() || $this->hasResourceDisplayColumns(),
            'Tabela security_crud_resources possui display_name/plural_display_name.'
        );

        $failed = collect($checks)->where('ok', false)->count();

        return [
            'ok' => $failed === 0,
            'failed' => $failed,
            'checks' => $checks,
        ];
    }

    protected function hasResourceDisplayColumns(): bool
    {
        try {
            return Schema::hasColumn('security_crud_resources', 'display_name')
                && Schema::hasColumn('security_crud_resources', 'plural_display_name');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function check(string $key, bool $ok, string $message): array
    {
        return [
            'key' => $key,
            'ok' => $ok,
            'message' => $message,
        ];
    }
}
