<?php

namespace App\Models\Security;

use App\Models\BaseModel;

class SecurityCrudResource extends BaseModel
{
    protected $table = 'security_crud_resources';

    protected $fillable = [
        'module',
        'technical_name',
        'display_name',
        'plural_display_name',
        'description',
        'model_class',
        'controller_class',
        'route_prefix',
        'table_name',
        'icon',
        'sensitive',
        'tenant_visible',
        'super_admin_only',
        'base_model_detected',
        'base_controller_detected',
        'source',
        'actions',
        'enabled',
    ];

    protected $casts = [
        'sensitive' => 'boolean',
        'tenant_visible' => 'boolean',
        'super_admin_only' => 'boolean',
        'base_model_detected' => 'boolean',
        'base_controller_detected' => 'boolean',
        'enabled' => 'boolean',
        'actions' => 'array',
    ];

    protected bool $auditEnabled = false;
}
