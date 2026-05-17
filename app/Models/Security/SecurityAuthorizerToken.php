<?php

namespace App\Models\Security;

use App\Models\BaseModel;
use App\Models\Empresa;

class SecurityAuthorizerToken extends BaseModel
{
    protected $table = 'security_authorizer_tokens';

    protected $fillable = [
        'empresa_id',
        'security_authorizer_id',
        'name',
        'token_hash',
        'enabled',
        'expires_at',
        'last_used_at',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'enabled' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected bool $auditEnabled = false;

    public function empresa() { return $this->belongsTo(Empresa::class, 'empresa_id'); }
    public function authorizer() { return $this->belongsTo(SecurityAuthorizer::class, 'security_authorizer_id'); }
}
