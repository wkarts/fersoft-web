<?php

namespace App\Services\Security;

use App\Models\Empresa;
use App\Models\Security\EmpresaSecuritySetting;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;

class SecurityFeatureService
{
    public function migrationsReady(): bool
    {
        return app(SecurityMigrationStateService::class)->isReady();
    }

    public function missingMigrationTables(): array
    {
        return app(SecurityMigrationStateService::class)->missingTables();
    }

    protected function defaultSetting(?int $empresaId = null): EmpresaSecuritySetting
    {
        $setting = new EmpresaSecuritySetting();
        $setting->empresa_id = $empresaId;
        $setting->platform_enabled = true;
        $setting->tenant_enabled = false;
        $setting->enforcement_enabled = false;
        $setting->legacy_password_disabled = false;
        $setting->google_auth_required = true;
        $setting->audit_sensitive_export_enabled = false;
        $setting->restore_from_audit_enabled = false;

        return $setting;
    }
    public function currentEmpresaId(): ?int
    {
        return app(SecurityTenantContextService::class)->currentEmpresaId();
    }

    public function currentUserId(): ?int
    {
        return app(SecurityTenantContextService::class)->currentUserId();
    }

    public function isSuperAdmin(): bool
    {
        return app(SecurityTenantContextService::class)->isSuperAdmin();
    }

    public function getOrCreateSetting(?int $empresaId = null): EmpresaSecuritySetting
    {
        $empresaId = $empresaId ?? $this->currentEmpresaId();

        if (!$this->migrationsReady()) {
            return $this->defaultSetting($empresaId);
        }

        return EmpresaSecuritySetting::firstOrCreate(
            ['empresa_id' => $empresaId],
            [
                'platform_enabled' => true,
                'tenant_enabled' => false,
                'enforcement_enabled' => false,
                'legacy_password_disabled' => false,
                'google_auth_required' => true,
                'audit_sensitive_export_enabled' => false,
                'restore_from_audit_enabled' => false,
            ]
        );
    }

    public function tenantEnabled(?int $empresaId = null): bool
    {
        return (bool) $this->getOrCreateSetting($empresaId)->tenant_enabled;
    }

    public function enforcementEnabled(?int $empresaId = null): bool
    {
        $setting = $this->getOrCreateSetting($empresaId);
        return (bool) ($setting->platform_enabled && $setting->tenant_enabled && $setting->enforcement_enabled);
    }

    public function enableForTenant(?int $empresaId = null, ?int $userId = null): EmpresaSecuritySetting
    {
        if (!$this->migrationsReady()) {
            throw new \RuntimeException('As migrations da Segurança de Operações ainda não foram executadas. Rode php artisan migrate antes de habilitar o recurso.');
        }

        $setting = $this->getOrCreateSetting($empresaId);
        $setting->fill([
            'tenant_enabled' => true,
            'google_auth_required' => true,
            'enabled_at' => $setting->enabled_at ?: now(),
            'enabled_by' => $setting->enabled_by ?: ($userId ?? $this->currentUserId()),
        ]);
        $setting->save();

        return $setting;
    }

    public function globalSummary(): array
    {
        if (!$this->migrationsReady()) {
            return [
                'total' => 0,
                'tenant_enabled' => 0,
                'setup_completed' => 0,
                'enforcement_enabled' => 0,
                'legacy_enabled' => 0,
                'rows' => collect(),
                'migrations_ready' => false,
                'missing_tables' => $this->missingMigrationTables(),
            ];
        }

        $empresas = Empresa::query()
            ->with(['planoEmpresa'])
            ->orderBy('id', 'desc')
            ->get();

        $settings = EmpresaSecuritySetting::query()
            ->whereIn('empresa_id', $empresas->pluck('id'))
            ->get()
            ->keyBy('empresa_id');

        $rows = $empresas->map(function ($empresa) use ($settings) {
            $setting = $settings->get($empresa->id);

            return [
                'empresa' => $empresa,
                'setting' => $setting,
                'tenant_enabled' => (bool) optional($setting)->tenant_enabled,
                'setup_completed' => !empty(optional($setting)->setup_completed_at),
                'enforcement_enabled' => (bool) optional($setting)->enforcement_enabled,
                'legacy_password_disabled' => (bool) optional($setting)->legacy_password_disabled,
            ];
        });

        return [
            'total' => $rows->count(),
            'tenant_enabled' => $rows->where('tenant_enabled', true)->count(),
            'setup_completed' => $rows->where('setup_completed', true)->count(),
            'enforcement_enabled' => $rows->where('enforcement_enabled', true)->count(),
            'legacy_enabled' => $rows->where('legacy_password_disabled', false)->count(),
            'rows' => $rows,
        ];
    }
}
