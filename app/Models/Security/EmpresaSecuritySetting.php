<?php

namespace App\Models\Security;

use App\Models\BaseModel;
use App\Models\Empresa;
use App\Models\Usuario;

class EmpresaSecuritySetting extends BaseModel
{
    protected $table = 'empresa_security_settings';

    protected $fillable = [
        'empresa_id',
        'platform_enabled',
        'tenant_enabled',
        'enforcement_enabled',
        'legacy_password_disabled',
        'google_auth_required',
        'audit_sensitive_export_enabled',
        'restore_from_audit_enabled',
        'setup_completed_at',
        'enabled_at',
        'enabled_by',
    ];

    protected $casts = [
        'platform_enabled' => 'boolean',
        'tenant_enabled' => 'boolean',
        'enforcement_enabled' => 'boolean',
        'legacy_password_disabled' => 'boolean',
        'google_auth_required' => 'boolean',
        'audit_sensitive_export_enabled' => 'boolean',
        'restore_from_audit_enabled' => 'boolean',
        'setup_completed_at' => 'datetime',
        'enabled_at' => 'datetime',
    ];

    protected bool $auditEnabled = false;

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function enabledBy()
    {
        return $this->belongsTo(Usuario::class, 'enabled_by');
    }
}
