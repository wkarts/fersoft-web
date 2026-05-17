<?php

namespace App\Models\Security;

use App\Models\BaseModel;
use App\Models\Empresa;
use App\Models\Usuario;

class SecurityOperationAuthorization extends BaseModel
{
    protected $table = 'security_operation_authorizations';

    protected $fillable = [
        'empresa_id',
        'security_crud_resource_id',
        'executor_user_id',
        'authorizer_user_id',
        'action',
        'record_id',
        'authorization_token_hash',
        'expires_at',
        'used_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $hidden = ['authorization_token_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected bool $auditEnabled = false;

    public function empresa() { return $this->belongsTo(Empresa::class, 'empresa_id'); }
    public function resource() { return $this->belongsTo(SecurityCrudResource::class, 'security_crud_resource_id'); }
    public function executor() { return $this->belongsTo(Usuario::class, 'executor_user_id'); }
    public function authorizer() { return $this->belongsTo(Usuario::class, 'authorizer_user_id'); }
}
