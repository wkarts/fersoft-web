<?php

namespace App\Models\Security;

use App\Models\BaseModel;
use App\Models\Empresa;

class SecurityAuthorizerResource extends BaseModel
{
    protected $table = 'security_authorizer_resources';

    protected $fillable = [
        'empresa_id',
        'security_authorizer_id',
        'security_crud_resource_id',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
        'can_restore',
        'can_export',
        'can_print',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_restore' => 'boolean',
        'can_export' => 'boolean',
        'can_print' => 'boolean',
    ];

    protected bool $auditEnabled = false;

    public function empresa() { return $this->belongsTo(Empresa::class, 'empresa_id'); }
    public function authorizer() { return $this->belongsTo(SecurityAuthorizer::class, 'security_authorizer_id'); }
    public function resource() { return $this->belongsTo(SecurityCrudResource::class, 'security_crud_resource_id'); }
}
