<?php

namespace App\Services\Security;

use App\Models\ConfigNota;
use App\Models\Security\SecurityAuthorizer;
use App\Models\Security\SecurityAuthorizerResource;
use App\Models\Security\SecurityOperationAuthorization;
use App\Models\Security\SecurityCrudResource;
use App\Models\Usuario;
use App\Services\OtpService;
use App\Services\Security\SecurityAuthorizerOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SecurityOperationAuthorizationService
{
    public function createAuthorization(
        SecurityCrudResource $resource,
        string $action,
        ?int $empresaId,
        ?int $executorUserId,
        SecurityAuthorizer $authorizer,
        ?int $recordId,
        Request $request
    ): array {
        $plainToken = Str::random(64);

        $authorization = SecurityOperationAuthorization::create([
            'empresa_id' => $empresaId,
            'security_crud_resource_id' => $resource->id,
            'executor_user_id' => $executorUserId,
            'authorizer_user_id' => $authorizer->usuario_id,
            'action' => $action,
            'record_id' => $recordId,
            'authorization_token_hash' => Hash::make($plainToken),
            'expires_at' => now()->addMinutes(5),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'metadata' => [
                'resource' => $resource->model_class,
                'route' => $request->path(),
                'generated_by' => 'security_operation_authorization_service',
            ],
        ]);

        return [
            'authorization' => $authorization,
            'plain_token' => $plainToken,
        ];
    }


    public function createSystemAuthorization(
        SecurityCrudResource $resource,
        string $action,
        ?int $empresaId,
        ?int $executorUserId,
        ?int $recordId,
        Request $request,
        string $source = 'security_operation_system_authorization'
    ): array {
        $plainToken = Str::random(64);

        $authorization = SecurityOperationAuthorization::create([
            'empresa_id' => $empresaId,
            'security_crud_resource_id' => $resource->id,
            'executor_user_id' => $executorUserId,
            'authorizer_user_id' => null,
            'action' => $action,
            'record_id' => $recordId,
            'authorization_token_hash' => Hash::make($plainToken),
            'expires_at' => now()->addMinutes(5),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'metadata' => [
                'resource' => $resource->model_class,
                'route' => $request->path(),
                'generated_by' => $source,
            ],
        ]);

        return [
            'authorization' => $authorization,
            'plain_token' => $plainToken,
        ];
    }

    public function authorizeWithLegacyPassword(
        SecurityCrudResource $resource,
        string $action,
        ?int $empresaId,
        ?int $executorUserId,
        ?int $recordId,
        ?string $plainPassword,
        Request $request
    ): array {
        $plainPassword = trim((string) $plainPassword);

        if ($plainPassword === '' || !$empresaId) {
            throw ValidationException::withMessages([
                'security_legacy_password' => ['Informe a senha legada de liberação.'],
            ]);
        }

        $config = ConfigNota::query()->where('empresa_id', $empresaId)->first();

        if (!$config || empty($config->senha_remover) || (string) $config->senha_remover !== md5($plainPassword)) {
            throw ValidationException::withMessages([
                'security_legacy_password' => ['Senha legada de liberação inválida.'],
            ]);
        }

        return $this->createSystemAuthorization(
            $resource,
            $action,
            $empresaId,
            $executorUserId,
            $recordId,
            $request,
            'security_operation_legacy_password_authorization'
        );
    }

    public function consumeToken(
        SecurityCrudResource $resource,
        string $action,
        ?int $empresaId,
        ?int $executorUserId,
        ?int $recordId,
        ?string $plainToken
    ): bool {
        $plainToken = trim((string) $plainToken);

        if ($plainToken === '') {
            return false;
        }

        $query = SecurityOperationAuthorization::query()
            ->where('security_crud_resource_id', $resource->id)
            ->where('action', $action)
            ->whereNull('used_at')
            ->where(function ($builder) {
                $builder->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        } else {
            $query->whereNull('empresa_id');
        }

        if ($executorUserId) {
            $query->where('executor_user_id', $executorUserId);
        }

        if ($recordId) {
            $query->where(function ($builder) use ($recordId) {
                $builder->whereNull('record_id')->orWhere('record_id', $recordId);
            });
        }

        foreach ($query->orderByDesc('id')->limit(20)->get() as $authorization) {
            if (Hash::check($plainToken, (string) $authorization->authorization_token_hash)) {
                $authorization->used_at = now();
                $authorization->save();
                return true;
            }
        }

        return false;
    }

    public function authorizeWithAuthorizerCredentials(
        SecurityCrudResource $resource,
        string $action,
        string $protectionType,
        ?int $empresaId,
        ?int $executorUserId,
        ?int $recordId,
        string $authorizerLogin,
        ?string $token,
        ?string $otp,
        Request $request
    ): array {
        $authorizerLogin = trim($authorizerLogin);

        if ($authorizerLogin === '') {
            throw ValidationException::withMessages([
                'authorizer_login' => ['Informe o usuário autorizador.'],
            ]);
        }

        $usuario = Usuario::query()
            ->where(function ($builder) use ($authorizerLogin) {
                $builder->where('login', $authorizerLogin)
                    ->orWhere('email', $authorizerLogin)
                    ->orWhere('id', is_numeric($authorizerLogin) ? (int) $authorizerLogin : 0);
            })
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where(function ($builder) use ($empresaId) {
                    $builder->where('empresa_id', $empresaId)->orWhereNull('empresa_id');
                });
            })
            ->first();

        if (!$usuario) {
            throw ValidationException::withMessages([
                'authorizer_login' => ['Usuário autorizador não encontrado.'],
            ]);
        }

        $authorizer = SecurityAuthorizer::query()
            ->where('usuario_id', $usuario->id)
            ->where('enabled', true)
            ->where(function ($builder) use ($empresaId) {
                if ($empresaId) {
                    $builder->where('empresa_id', $empresaId)->orWhereNull('empresa_id');
                } else {
                    $builder->whereNull('empresa_id');
                }
            })
            ->orderByRaw('CASE WHEN empresa_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->first();

        if (!$authorizer) {
            throw ValidationException::withMessages([
                'authorizer_login' => ['Este usuário não possui privilégio de autorizador.'],
            ]);
        }

        if (!$this->canAuthorize($authorizer, $resource, $action, $empresaId)) {
            throw ValidationException::withMessages([
                'authorizer_login' => ['Este autorizador não possui permissão para liberar esta operação.'],
            ]);
        }

        $tokenOk = false;
        $otpOk = false;

        if (in_array($protectionType, ['authorizer_token', 'authorizer_token_or_otp'], true)) {
            $tokenOk = $this->validateAuthorizerToken($authorizer, $token);
        }

        if (in_array($protectionType, ['authorizer_otp', 'authorizer_token_or_otp'], true)) {
            $otpOk = $this->validateAuthorizerOtp($authorizer, $usuario, $otp);
        }

        if ($protectionType === 'authorizer_token' && !$tokenOk) {
            throw ValidationException::withMessages([
                'authorizer_token' => ['Token do autorizador inválido.'],
            ]);
        }

        if ($protectionType === 'authorizer_otp' && !$otpOk) {
            throw ValidationException::withMessages([
                'authorizer_otp' => ['Código Google Authenticator do autorizador inválido.'],
            ]);
        }

        if ($protectionType === 'authorizer_token_or_otp' && !$tokenOk && !$otpOk) {
            throw ValidationException::withMessages([
                'authorizer_token' => ['Informe um token válido ou um código Google Authenticator válido.'],
            ]);
        }

        return $this->createAuthorization($resource, $action, $empresaId, $executorUserId, $authorizer, $recordId, $request);
    }

    protected function canAuthorize(SecurityAuthorizer $authorizer, SecurityCrudResource $resource, string $action, ?int $empresaId): bool
    {
        $column = 'can_authorize_' . $action;

        if ($authorizer->can_authorize_all_resources && (bool) ($authorizer->{$column} ?? false)) {
            return true;
        }

        $resourceRule = SecurityAuthorizerResource::query()
            ->where('security_authorizer_id', $authorizer->id)
            ->where('security_crud_resource_id', $resource->id)
            ->where(function ($builder) use ($empresaId) {
                if ($empresaId) {
                    $builder->where('empresa_id', $empresaId)->orWhereNull('empresa_id');
                } else {
                    $builder->whereNull('empresa_id');
                }
            })
            ->orderByRaw('CASE WHEN empresa_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->first();

        if ($resourceRule) {
            $resourceColumn = 'can_' . $action;
            return (bool) ($resourceRule->{$resourceColumn} ?? false);
        }

        return (bool) ($authorizer->{$column} ?? false);
    }

    protected function validateAuthorizerToken(SecurityAuthorizer $authorizer, ?string $plainToken): bool
    {
        $plainToken = trim((string) $plainToken);

        if ($plainToken === '') {
            return false;
        }

        $tokens = $authorizer->tokens()
            ->where('enabled', true)
            ->where(function ($builder) {
                $builder->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->get();

        foreach ($tokens as $token) {
            if (Hash::check($plainToken, (string) $token->token_hash)) {
                $token->last_used_at = now();
                $token->save();
                return true;
            }
        }

        return false;
    }

    protected function validateAuthorizerOtp(SecurityAuthorizer $authorizer, Usuario $usuario, ?string $otp): bool
    {
        $otp = trim((string) $otp);

        if ($otp === '') {
            return false;
        }

        if ($authorizer->hasOperationOtp()) {
            return app(SecurityAuthorizerOtpService::class)->verify($authorizer, $otp, true);
        }

        // Compatibilidade: se o autorizador ainda não configurou OTP operacional,
        // mantém o OTP do usuário como fallback para não quebrar tenants existentes.
        if (method_exists($usuario, 'hasOtp') && $usuario->hasOtp()) {
            return app(OtpService::class)->verifyForUser($usuario, $otp);
        }

        return false;
    }
}
