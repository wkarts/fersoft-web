<?php

namespace App\Services\Security;

use App\Models\Security\SecurityCrudProtectionRule;
use App\Models\Security\SecurityCrudResource;

class SecurityCrudProtectionService
{
    public const PROTECTION_TYPES = [
        'none' => 'Sem proteção extra',
        'authorizer_token' => 'Token de autorizador',
        'authorizer_otp' => 'App autenticador do autorizador',
        'authorizer_token_or_otp' => 'Token ou app autenticador',
        'blocked' => 'Bloqueado',
        'password_legacy' => 'Senha legada',
    ];

    public function createOrUpdate(array $data): SecurityCrudProtectionRule
    {
        $resource = SecurityCrudResource::findOrFail((int) $data['security_crud_resource_id']);

        $attributes = [
            'empresa_id' => $data['empresa_id'] ?? null,
            'security_crud_resource_id' => $resource->id,
            'action' => $data['action'],
        ];

        $values = [
            'protection_type' => $data['protection_type'] ?? 'none',
            'requires_authorizer' => !empty($data['requires_authorizer']),
            'allow_self_authorization' => !empty($data['allow_self_authorization']),
            'bypass_super_admin' => array_key_exists('bypass_super_admin', $data) ? !empty($data['bypass_super_admin']) : true,
            'bypass_company_admin' => !empty($data['bypass_company_admin']),
            'enabled' => array_key_exists('enabled', $data) ? !empty($data['enabled']) : true,
            'message' => $data['message'] ?? null,
        ];

        $existing = SecurityCrudProtectionRule::query()
            ->where('empresa_id', $attributes['empresa_id'])
            ->where('security_crud_resource_id', $attributes['security_crud_resource_id'])
            ->where('action', $attributes['action'])
            ->first();

        if ($existing) {
            $existing->fill($values);
            $existing->save();
            return $existing;
        }

        return SecurityCrudProtectionRule::create(array_merge($attributes, $values));
    }

    public function resolve(SecurityCrudResource $resource, string $action, ?int $empresaId): ?SecurityCrudProtectionRule
    {
        return SecurityCrudProtectionRule::query()
            ->where('security_crud_resource_id', $resource->id)
            ->where('action', $action)
            ->where('enabled', true)
            ->where(function ($builder) use ($empresaId) {
                if ($empresaId) {
                    $builder->where('empresa_id', $empresaId)
                        ->orWhereNull('empresa_id');
                } else {
                    $builder->whereNull('empresa_id');
                }
            })
            ->orderByRaw('CASE WHEN empresa_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->first();
    }
}
