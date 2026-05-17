<?php

namespace App\Models\Security;

use App\Models\BaseModel;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\PerfilAcesso;

class SecurityCrudPermission extends BaseModel
{
    protected $table = 'security_crud_permissions';

    protected $fillable = [
        'empresa_id',
        'security_crud_resource_id',
        'perfil_acesso_id',
        'usuario_id',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
        'can_restore',
        'can_export',
        'can_print',
        'enabled',
        'source',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_restore' => 'boolean',
        'can_export' => 'boolean',
        'can_print' => 'boolean',
        'enabled' => 'boolean',
    ];

    protected bool $auditEnabled = false;

    public function empresa() { return $this->belongsTo(Empresa::class, 'empresa_id'); }
    public function resource() { return $this->belongsTo(SecurityCrudResource::class, 'security_crud_resource_id'); }
    public function perfil() { return $this->belongsTo(PerfilAcesso::class, 'perfil_acesso_id'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'usuario_id'); }
}
