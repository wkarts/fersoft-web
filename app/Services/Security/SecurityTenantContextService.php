<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SecurityTenantContextService
{
    public function currentEmpresaId(?Request $request = null, bool $preferRequest = true): ?int
    {
        $value = null;

        if ($request && $preferRequest) {
            $value = $this->firstFilled([
                $request->attributes->get('empresa_id'),
                $request->route('empresa_id'),
                $request->input('empresa_id'),
                $request->query('empresa_id'),
            ]);
        }

        if ($value === null) {
            $session = Session::get('user_logged', []);
            $value = $this->firstFilled([
                data_get($session, 'empresa'),
                data_get($session, 'empresa_id'),
                Session::get('empresa_id'),
                optional(Auth::user())->empresa_id,
                optional(Auth::user())->empresa,
            ]);
        }

        return $this->toPositiveInt($value);
    }

    public function currentUserId(?Request $request = null, bool $preferRequest = false): ?int
    {
        $value = null;

        if ($request && $preferRequest) {
            $value = $this->firstFilled([
                $request->attributes->get('usuario_id'),
                $request->route('usuario_id'),
                $request->input('usuario_id'),
                $request->query('usuario_id'),
            ]);
        }

        if ($value === null) {
            $session = Session::get('user_logged', []);
            $value = $this->firstFilled([
                data_get($session, 'id'),
                data_get($session, 'usuario_id'),
                Session::get('usuario_id'),
                optional(Auth::user())->id,
            ]);
        }

        return $this->toPositiveInt($value);
    }

    public function currentPerfilId(?Request $request = null, bool $preferRequest = false): ?int
    {
        $value = null;

        if ($request && $preferRequest) {
            $value = $this->firstFilled([
                $request->attributes->get('perfil_id'),
                $request->route('perfil_id'),
                $request->input('perfil_id'),
                $request->query('perfil_id'),
                $request->input('perfil_acesso_id'),
            ]);
        }

        if ($value === null) {
            $session = Session::get('user_logged', []);
            $value = $this->firstFilled([
                data_get($session, 'perfil_id'),
                data_get($session, 'perfil_acesso_id'),
                data_get($session, 'perfil'),
                optional(Auth::user())->perfil_id,
                optional(Auth::user())->perfil_acesso_id,
            ]);
        }

        return $this->toPositiveInt($value);
    }

    public function isSuperAdmin(?Request $request = null): bool
    {
        $session = Session::get('user_logged', []);

        if (!empty(data_get($session, 'super'))) {
            return true;
        }

        if ($request && $request->attributes->get('security_super_admin') === true) {
            return true;
        }

        $user = Auth::user();
        return (bool) (optional($user)->super ?? false);
    }

    public function mergeIntoRequest(Request $request): Request
    {
        $payload = [];

        if (!$request->input('empresa_id') && !$request->attributes->get('empresa_id')) {
            $empresaId = $this->currentEmpresaId($request, false);
            if ($empresaId) {
                $payload['empresa_id'] = $empresaId;
                $request->attributes->set('empresa_id', $empresaId);
            }
        }

        if (!$request->input('usuario_id') && !$request->attributes->get('usuario_id')) {
            $usuarioId = $this->currentUserId($request, false);
            if ($usuarioId) {
                $request->attributes->set('usuario_id', $usuarioId);
            }
        }

        if ($payload) {
            $request->merge($payload);
        }

        return $request;
    }

    protected function firstFilled(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '' && $value !== 'null') {
                return $value;
            }
        }

        return null;
    }

    protected function toPositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        if (is_object($value) && method_exists($value, 'getKey')) {
            $value = $value->getKey();
        }

        if (!is_numeric($value)) {
            return null;
        }

        $value = (int) $value;
        return $value > 0 ? $value : null;
    }
}
