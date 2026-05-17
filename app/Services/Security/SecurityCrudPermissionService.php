<?php

namespace App\Services\Security;

use App\Models\Security\SecurityCrudPermission;
use App\Models\Security\SecurityCrudResource;

class SecurityCrudPermissionService
{
    public const ACTIONS = [
        'view' => 'Visualizar',
        'create' => 'Inserir',
        'edit' => 'Editar',
        'delete' => 'Excluir',
        'restore' => 'Restaurar',
        'export' => 'Exportar',
        'print' => 'Imprimir',
    ];

    public function defaultPermissionsForResource(SecurityCrudResource $resource): array
    {
        $resourceActions = is_array($resource->actions) ? $resource->actions : [];

        return [
            'can_view' => (bool) ($resourceActions['view'] ?? true),
            'can_create' => (bool) ($resourceActions['create'] ?? true),
            'can_edit' => (bool) ($resourceActions['edit'] ?? true),
            'can_delete' => (bool) ($resourceActions['delete'] ?? true),
            'can_restore' => (bool) ($resourceActions['restore'] ?? false),
            'can_export' => (bool) ($resourceActions['export'] ?? true),
            'can_print' => (bool) ($resourceActions['print'] ?? true),
        ];
    }

    public function createOrUpdate(array $data): SecurityCrudPermission
    {
        $resource = SecurityCrudResource::findOrFail((int) $data['security_crud_resource_id']);
        $defaults = $this->defaultPermissionsForResource($resource);

        $attributes = [
            'empresa_id' => $data['empresa_id'] ?? null,
            'security_crud_resource_id' => $resource->id,
            'perfil_acesso_id' => $data['perfil_acesso_id'] ?? null,
            'usuario_id' => $data['usuario_id'] ?? null,
        ];

        $values = array_merge($defaults, [
            'can_view' => !empty($data['can_view']),
            'can_create' => !empty($data['can_create']),
            'can_edit' => !empty($data['can_edit']),
            'can_delete' => !empty($data['can_delete']),
            'can_restore' => !empty($data['can_restore']),
            'can_export' => !empty($data['can_export']),
            'can_print' => !empty($data['can_print']),
            'enabled' => array_key_exists('enabled', $data) ? !empty($data['enabled']) : true,
            'source' => $data['source'] ?? 'manual',
            'updated_by' => $data['updated_by'] ?? app(SecurityTenantContextService::class)->currentUserId(),
        ]);

        $existing = SecurityCrudPermission::query()
            ->where('empresa_id', $attributes['empresa_id'])
            ->where('security_crud_resource_id', $attributes['security_crud_resource_id'])
            ->where('perfil_acesso_id', $attributes['perfil_acesso_id'])
            ->where('usuario_id', $attributes['usuario_id'])
            ->first();

        if ($existing) {
            $existing->fill($values);
            $existing->save();
            return $existing;
        }

        $values['created_by'] = $data['created_by'] ?? app(SecurityTenantContextService::class)->currentUserId();
        return SecurityCrudPermission::create(array_merge($attributes, $values));
    }

    public function can(SecurityCrudResource $resource, string $action, ?int $empresaId, ?int $usuarioId = null, ?int $perfilId = null): bool
    {
        $column = 'can_' . $action;

        if (!in_array($column, ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_restore', 'can_export', 'can_print'], true)) {
            return false;
        }

        $query = SecurityCrudPermission::query()
            ->where('security_crud_resource_id', $resource->id)
            ->where('enabled', true)
            ->where(function ($builder) use ($empresaId) {
                $builder->whereNull('empresa_id');

                if ($empresaId) {
                    $builder->orWhere('empresa_id', $empresaId);
                }
            })
            ->orderByRaw('CASE WHEN usuario_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->orderByRaw('CASE WHEN perfil_acesso_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->orderByRaw('CASE WHEN empresa_id IS NOT NULL THEN 1 ELSE 0 END DESC');

        $query->where(function ($builder) use ($usuarioId, $perfilId) {
            $builder->where(function ($q) {
                $q->whereNull('usuario_id')->whereNull('perfil_acesso_id');
            });

            if ($perfilId) {
                $builder->orWhere(function ($q) use ($perfilId) {
                    $q->where('perfil_acesso_id', $perfilId)->whereNull('usuario_id');
                });
            }

            if ($usuarioId) {
                $builder->orWhere('usuario_id', $usuarioId);
            }
        });

        $permission = $query->first();

        if ($permission) {
            return (bool) $permission->{$column};
        }

        $defaults = $this->defaultPermissionsForResource($resource);
        return (bool) ($defaults[$column] ?? true);
    }
}
