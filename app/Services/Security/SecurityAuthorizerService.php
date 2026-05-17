<?php

namespace App\Services\Security;

use App\Models\Security\SecurityAuthorizer;
use App\Models\Security\SecurityAuthorizerToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SecurityAuthorizerService
{
    public function createOrUpdate(array $data): SecurityAuthorizer
    {
        $attributes = [
            'empresa_id' => $data['empresa_id'] ?? null,
            'usuario_id' => (int) $data['usuario_id'],
        ];

        $values = [
            'enabled' => array_key_exists('enabled', $data) ? !empty($data['enabled']) : true,
            'can_authorize_all_resources' => !empty($data['can_authorize_all_resources']),
            'can_authorize_view' => !empty($data['can_authorize_view']),
            'can_authorize_create' => !empty($data['can_authorize_create']),
            'can_authorize_edit' => !empty($data['can_authorize_edit']),
            'can_authorize_delete' => !empty($data['can_authorize_delete']),
            'can_authorize_restore' => !empty($data['can_authorize_restore']),
            'can_authorize_export' => !empty($data['can_authorize_export']),
            'can_authorize_print' => !empty($data['can_authorize_print']),
        ];

        $authorizer = SecurityAuthorizer::query()
            ->where('empresa_id', $attributes['empresa_id'])
            ->where('usuario_id', $attributes['usuario_id'])
            ->first();

        if ($authorizer) {
            $authorizer->fill($values);
            $authorizer->save();
            return $authorizer;
        }

        return SecurityAuthorizer::create(array_merge($attributes, $values));
    }

    public function createToken(SecurityAuthorizer $authorizer, array $data): array
    {
        $plainToken = trim((string) ($data['token'] ?? ''));

        if ($plainToken === '') {
            $plainToken = strtoupper(Str::random(10));
        }

        $token = SecurityAuthorizerToken::create([
            'empresa_id' => $authorizer->empresa_id,
            'security_authorizer_id' => $authorizer->id,
            'name' => $data['name'] ?? 'Token principal',
            'token_hash' => Hash::make($plainToken),
            'enabled' => true,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return [
            'token' => $token,
            'plain_token' => $plainToken,
        ];
    }
}
