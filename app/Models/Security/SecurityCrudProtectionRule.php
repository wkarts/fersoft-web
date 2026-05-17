<?php

namespace App\Models\Security;

use App\Models\BaseModel;
use App\Models\Empresa;

class SecurityCrudProtectionRule extends BaseModel
{
    protected $table = 'security_crud_protection_rules';

    protected $fillable = [
        'empresa_id',
        'security_crud_resource_id',
        'action',
        'protection_type',
        'requires_authorizer',
        'allow_self_authorization',
        'bypass_super_admin',
        'bypass_company_admin',
        'enabled',
        'message',
        'source',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requires_authorizer' => 'boolean',
        'allow_self_authorization' => 'boolean',
        'bypass_super_admin' => 'boolean',
        'bypass_company_admin' => 'boolean',
        'enabled' => 'boolean',
    ];

    protected bool $auditEnabled = false;

    public function empresa() { return $this->belongsTo(Empresa::class, 'empresa_id'); }
    public function resource() { return $this->belongsTo(SecurityCrudResource::class, 'security_crud_resource_id'); }
}
