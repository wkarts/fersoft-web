<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Security\SecurityCrudGuardService;
use App\Services\Security\SecurityOperationContextService;
use App\Services\Security\SecurityTenantContextService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait UsesSecurityOperationContext
{
    protected function shareSecurityOperationContext(string $modelClass, ?int $recordId = null, ?string $screen = null, ?string $basePath = null): void
    {
        view()->share('securityOperationContext', app(SecurityOperationContextService::class)->make(
            $modelClass,
            $recordId,
            $screen,
            $basePath
        ));
    }

    protected function assertSecurityOperation(Request $request, string $modelClass, string $action, ?int $recordId = null): void
    {
        $perfilId = null;
        $userLogged = session('user_logged', []);

        foreach (['perfil_acesso_id', 'perfil_id', 'perfil'] as $key) {
            if (!empty($userLogged[$key]) && is_numeric($userLogged[$key])) {
                $perfilId = (int) $userLogged[$key];
                break;
            }
        }

        try {
            app(SecurityCrudGuardService::class)->assertAllowed(
                app(SecurityOperationContextService::class)->normalizeModelClass($modelClass),
                $action,
                $this->securityEmpresaId($request),
                !empty($userLogged['id']) ? (int) $userLogged['id'] : null,
                $request,
                $recordId,
                $perfilId
            );
        } catch (ValidationException $e) {
            $message = 'Operação bloqueada pela Segurança de Operações.';

            foreach ($e->errors() as $fieldErrors) {
                if (!empty($fieldErrors[0])) {
                    $message = $fieldErrors[0];
                    break;
                }
            }

            throw new \Exception($message);
        }
    }

    protected function securityEmpresaId(Request $request): ?int
    {
        $empresaId = $request->input('empresa_id')
            ?: $request->route('empresa_id')
            ?: session('user_logged.empresa')
            ?: session('empresa_id');

        return is_numeric($empresaId) ? (int) $empresaId : null;
    }
}
