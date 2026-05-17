<?php

namespace App\Http\Middleware;

use App\Services\Security\SecurityCrudGuardService;
use App\Services\Security\SecurityOperationContextService;
use App\Services\Security\SecurityTenantContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CheckCrudSecurity
{
    public function __construct(
        protected SecurityCrudGuardService $guardService,
        protected SecurityOperationContextService $contextService
    ) {}

    /**
     * Middleware opcional para controllers que não herdam BaseController.
     *
     * Uso básico:
     *   ->middleware('security.crud:App\\Models\\ContaPagar,delete')
     *
     * Uso com inferência automática:
     *   ->middleware('security.crud:ContaPagar,auto,id,/contas-pagar')
     *
     * Parâmetros:
     *   1. modelClass   App\\Models\\Produto, Produto ou App|Models|Produto
     *   2. action       view, create, edit, delete, restore, auto ou context
     *   3. recordParam  id, conta_pagar_id etc.
     *   4. basePath     /contas-pagar para o auto-bind frontend
     */
    public function handle(Request $request, Closure $next, string $modelClass, string $action = 'auto', string $recordParam = 'id', ?string $basePath = null)
    {
        $modelClass = $this->contextService->normalizeModelClass($modelClass);
        $recordId = $this->contextService->inferRecordId($request, $recordParam);
        $resolvedAction = $action === 'auto'
            ? $this->contextService->inferAction($request, 'view')
            : $action;

        if ($resolvedAction !== 'context') {
            try {
                $this->guardService->assertAllowed(
                    $modelClass,
                    $resolvedAction,
                    $this->empresaId($request),
                    $this->usuarioId(),
                    $request,
                    $recordId,
                    $this->perfilId()
                );
            } catch (ValidationException $e) {
                return $this->deny($request, $e);
            }
        }

        view()->share('securityOperationContext', $this->contextService->makeFromRequest(
            $request,
            $modelClass,
            $recordId,
            $resolvedAction,
            $basePath
        ));

        return $next($request);
    }

    protected function empresaId(Request $request): ?int
    {
        $empresaId = $request->empresa_id
            ?: ($request->input('empresa_id') ?: (session('user_logged.empresa') ?? null));

        return is_numeric($empresaId) ? (int) $empresaId : null;
    }

    protected function usuarioId(): ?int
    {
        $usuarioId = session('user_logged.id');

        return is_numeric($usuarioId) ? (int) $usuarioId : null;
    }

    protected function perfilId(): ?int
    {
        $userLogged = session('user_logged', []);

        foreach (['perfil_acesso_id', 'perfil_id', 'perfil'] as $key) {
            if (!empty($userLogged[$key]) && is_numeric($userLogged[$key])) {
                return (int) $userLogged[$key];
            }
        }

        return null;
    }

    protected function deny(Request $request, ValidationException $e)
    {
        $message = 'Operação bloqueada pela Segurança de Operações.';

        foreach ($e->errors() as $fieldErrors) {
            if (!empty($fieldErrors[0])) {
                $message = $fieldErrors[0];
                break;
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'errors' => $e->errors(),
            ], 403);
        }

        Session::flash('flash_error', $message);

        return redirect()->back()->withErrors($e->errors());
    }
}
