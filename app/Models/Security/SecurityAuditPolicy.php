<?php

namespace App\Models\Security;

use App\Models\BaseModel;
use App\Models\Empresa;

class SecurityAuditPolicy extends BaseModel
{
    protected $table = 'security_audit_policies';

    protected $fillable = [
        'empresa_id',
        'security_crud_resource_id',
        'model_class',
        'tenant_can_view',
        'tenant_can_view_json',
        'tenant_can_export_json',
        'tenant_can_restore',
        'super_admin_only',
        'sanitize_fields',
        'enabled',
    ];

    protected $casts = [
        'tenant_can_view' => 'boolean',
        'tenant_can_view_json' => 'boolean',
        'tenant_can_export_json' => 'boolean',
        'tenant_can_restore' => 'boolean',
        'super_admin_only' => 'boolean',
        'sanitize_fields' => 'array',
        'enabled' => 'boolean',
    ];

    protected bool $auditEnabled = false;

    public function empresa() { return $this->belongsTo(Empresa::class, 'empresa_id'); }
    public function resource() { return $this->belongsTo(SecurityCrudResource::class, 'security_crud_resource_id'); }
}
