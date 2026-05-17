<?php

namespace App\Models\Security;

use App\Models\BaseModel;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Helpers\EncryptionHelper;

class SecurityAuthorizer extends BaseModel
{
    protected $table = 'security_authorizers';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'enabled',
        'can_authorize_all_resources',
        'can_authorize_view',
        'can_authorize_create',
        'can_authorize_edit',
        'can_authorize_delete',
        'can_authorize_restore',
        'can_authorize_export',
        'can_authorize_print',
        'operation_otp_secret',
        'operation_otp_enabled',
        'operation_otp_confirmed_at',
        'operation_otp_last_used_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'can_authorize_all_resources' => 'boolean',
        'can_authorize_view' => 'boolean',
        'can_authorize_create' => 'boolean',
        'can_authorize_edit' => 'boolean',
        'can_authorize_delete' => 'boolean',
        'can_authorize_restore' => 'boolean',
        'can_authorize_export' => 'boolean',
        'can_authorize_print' => 'boolean',
        'operation_otp_enabled' => 'boolean',
        'operation_otp_confirmed_at' => 'datetime',
        'operation_otp_last_used_at' => 'datetime',
    ];

    protected bool $auditEnabled = false;

    protected $hidden = ['operation_otp_secret'];

    public function setOperationOtpSecretAttribute(?string $value): void
    {
        EncryptionHelper::initialize();

        $this->attributes['operation_otp_secret'] = $value ? EncryptionHelper::encrypt($value) : null;
    }

    public function getOperationOtpSecretAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        EncryptionHelper::initialize();

        return EncryptionHelper::decrypt($value);
    }

    public function hasOperationOtp(): bool
    {
        return $this->operation_otp_enabled && !empty($this->operation_otp_secret) && !empty($this->operation_otp_confirmed_at);
    }

    public function empresa() { return $this->belongsTo(Empresa::class, 'empresa_id'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'usuario_id'); }
    public function resources() { return $this->hasMany(SecurityAuthorizerResource::class, 'security_authorizer_id'); }
    public function tokens() { return $this->hasMany(SecurityAuthorizerToken::class, 'security_authorizer_id'); }
}
