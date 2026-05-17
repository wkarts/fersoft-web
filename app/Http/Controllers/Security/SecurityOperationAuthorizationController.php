<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\SecurityCrudResource;
use App\Services\Security\SecurityCrudProtectionService;
use App\Services\Security\SecurityFeatureService;
use App\Services\Security\SecurityOperationAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SecurityOperationAuthorizationController extends Controller
{
    public function resolve(Request $request, SecurityFeatureService $featureService, SecurityCrudProtectionService $protectionService)
    {
        $empresaId = $featureService->currentEmpresaId();

        if (!$featureService->enforcementEnabled($empresaId)) {
            return response()->json([
                'required' => false,
                'message' => 'Segurança de Operações sem enforcement ativo para esta empresa.',
            ]);
        }

        $resource = $this->resolveResourceFromRequest($request);
        $action = (string) $request->input('action', 'view');

        if (!$resource) {
            return response()->json([
                'required' => false,
                'message' => 'Recurso não encontrado no catálogo de segurança.',
            ]);
        }

        $rule = $protectionService->resolve($resource, $action, $empresaId);

        if (!$rule || !$rule->enabled || $rule->protection_type === 'none') {
            return response()->json([
                'required' => false,
                'resource' => $resource->display_name,
                'action' => $action,
            ]);
        }

        return response()->json([
            'required' => true,
            'resource' => $resource->display_name,
            'action' => $action,
            'protection_type' => $rule->protection_type,
            'message' => $rule->message ?: 'Esta operação exige liberação por autorizador.',
        ]);
    }

    public function authorize(
        Request $request,
        SecurityFeatureService $featureService,
        SecurityCrudProtectionService $protectionService,
        SecurityOperationAuthorizationService $authorizationService
    ) {
        $empresaId = $featureService->currentEmpresaId();
        $executorUserId = $featureService->currentUserId();
        $resource = $this->resolveResourceFromRequest($request);
        $action = (string) $request->input('action', 'view');
        $recordId = $request->filled('record_id') ? (int) $request->input('record_id') : null;

        if (!$resource) {
            throw ValidationException::withMessages([
                'resource' => ['Recurso não encontrado no catálogo de segurança.'],
            ]);
        }

        $rule = $protectionService->resolve($resource, $action, $empresaId);

        if (!$rule || !$rule->enabled || $rule->protection_type === 'none') {
            return response()->json([
                'authorized' => true,
                'authorization_token' => null,
                'message' => 'Esta operação não exige liberação extra.',
            ]);
        }

        if ($rule->protection_type === 'blocked') {
            throw ValidationException::withMessages([
                'security' => [$rule->message ?: 'Esta operação está bloqueada pela Segurança de Operações.'],
            ]);
        }

        if ($rule->protection_type === 'password_legacy') {
            $result = $authorizationService->authorizeWithLegacyPassword(
                $resource,
                $action,
                $empresaId,
                $executorUserId,
                $recordId,
                $request->input('security_legacy_password') ?: $request->input('senha_remover') ?: $request->input('senha'),
                $request
            );

            return response()->json([
                'authorized' => true,
                'authorization_token' => $result['plain_token'],
                'expires_at' => optional($result['authorization']->expires_at)->toDateTimeString(),
            ]);
        }

        $result = $authorizationService->authorizeWithAuthorizerCredentials(
            $resource,
            $action,
            $rule->protection_type,
            $empresaId,
            $executorUserId,
            $recordId,
            (string) $request->input('authorizer_login'),
            $request->input('authorizer_token'),
            $request->input('authorizer_otp'),
            $request
        );

        return response()->json([
            'authorized' => true,
            'authorization_token' => $result['plain_token'],
            'expires_at' => optional($result['authorization']->expires_at)->toDateTimeString(),
        ]);
    }

    protected function resolveResourceFromRequest(Request $request): ?SecurityCrudResource
    {
        if ($request->filled('security_crud_resource_id')) {
            return SecurityCrudResource::find((int) $request->input('security_crud_resource_id'));
        }

        if ($request->filled('resource')) {
            $resource = (string) $request->input('resource');

            return SecurityCrudResource::query()
                ->where('model_class', $resource)
                ->orWhere('route_prefix', $resource)
                ->orWhere('technical_name', $resource)
                ->first();
        }

        return null;
    }
}
