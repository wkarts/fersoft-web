<?php

namespace App\Services\Security;

use App\Models\ConfigNota;
use App\Models\Security\SecurityCrudResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class SecurityCrudGuardService
{
    public function __construct(
        protected SecurityFeatureService $featureService,
        protected SecurityCrudPermissionService $permissionService,
        protected SecurityCrudProtectionService $protectionService,
        protected SecurityOperationAuthorizationService $authorizationService
    ) {}

    public function assertAllowed(
        string $modelClass,
        string $action,
        ?int $empresaId,
        ?int $usuarioId,
        Request $request,
        ?int $recordId = null,
        ?int $perfilId = null
    ): void {
        if (!$this->featureService->enforcementEnabled($empresaId)) {
            return;
        }

        if ($this->featureService->isSuperAdmin()) {
            return;
        }

        $resource = $this->resolveResource($modelClass);

        if (!$resource) {
            return;
        }

        if (!$resource->enabled) {
            throw ValidationException::withMessages([
                'security' => ['Este recurso está desabilitado na Segurança de Operações.'],
            ]);
        }

        if ($resource->super_admin_only) {
            throw ValidationException::withMessages([
                'security' => ['Este recurso é restrito ao SuperAdmin da plataforma.'],
            ]);
        }

        if (!$resource->tenant_visible) {
            throw ValidationException::withMessages([
                'security' => ['Este recurso não está disponível para o tenant atual.'],
            ]);
        }

        if (!$this->permissionService->can($resource, $action, $empresaId, $usuarioId, $perfilId)) {
            throw ValidationException::withMessages([
                'security' => ['Você não possui permissão para executar esta operação.'],
            ]);
        }

        $rule = $this->protectionService->resolve($resource, $action, $empresaId);

        if (!$rule || !$rule->enabled || $rule->protection_type === 'none') {
            return;
        }

        if ($rule->protection_type === 'blocked') {
            throw ValidationException::withMessages([
                'security' => [$rule->message ?: 'Esta operação está bloqueada pela Segurança de Operações.'],
            ]);
        }

        if ($rule->protection_type === 'password_legacy') {
            if ($this->validateLegacyPassword($request, $empresaId)) {
                return;
            }

            throw ValidationException::withMessages([
                'security_legacy_password' => [$rule->message ?: 'Senha legada de liberação inválida.'],
            ]);
        }

        $temporaryToken = $request->input('security_operation_authorization_token')
            ?: $request->input('crud_authorization_token');

        if ($this->authorizationService->consumeToken($resource, $action, $empresaId, $usuarioId, $recordId, $temporaryToken)) {
            return;
        }

        $authorizerLogin = $request->input('security_authorizer_login') ?: $request->input('authorizer_login');
        $authorizerToken = $request->input('security_authorizer_token') ?: $request->input('authorizer_token');
        $authorizerOtp = $request->input('security_authorizer_otp') ?: $request->input('authorizer_otp');

        if ($authorizerLogin) {
            $authorization = $this->authorizationService->authorizeWithAuthorizerCredentials(
                $resource,
                $action,
                $rule->protection_type,
                $empresaId,
                $usuarioId,
                $recordId,
                $authorizerLogin,
                $authorizerToken,
                $authorizerOtp,
                $request
            );

            $this->authorizationService->consumeToken(
                $resource,
                $action,
                $empresaId,
                $usuarioId,
                $recordId,
                $authorization['plain_token']
            );

            return;
        }

        Session::flash('security_operation_required', [
            'resource' => $resource->plural_display_name ?: $resource->display_name,
            'action' => $action,
            'protection_type' => $rule->protection_type,
            'message' => $rule->message ?: 'Esta operação exige liberação por autorizador.',
        ]);

        throw ValidationException::withMessages([
            'security' => [$rule->message ?: 'Esta operação exige liberação por autorizador.'],
        ]);
    }

    protected function validateLegacyPassword(Request $request, ?int $empresaId): bool
    {
        $password = $request->input('security_legacy_password')
            ?: $request->input('senha_remover')
            ?: $request->input('senha');

        $password = trim((string) $password);

        if ($password === '' || !$empresaId) {
            return false;
        }

        $config = ConfigNota::query()->where('empresa_id', $empresaId)->first();

        if (!$config || empty($config->senha_remover)) {
            return false;
        }

        return (string) $config->senha_remover === md5($password);
    }

    public function resolveResource(string $modelClass): ?SecurityCrudResource
    {
        return SecurityCrudResource::query()
            ->where('model_class', $modelClass)
            ->first();
    }
}
