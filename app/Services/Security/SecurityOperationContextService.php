<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecurityOperationContextService
{
    public function make(string $modelClass, ?int $recordId = null, ?string $screen = null, ?string $basePath = null): array
    {
        $modelClass = $this->normalizeModelClass($modelClass);

        if (!$modelClass || !class_exists($modelClass)) {
            return ['enabled' => false];
        }

        $resource = [];
        if (method_exists($modelClass, 'securityResource')) {
            try {
                $resource = (array) $modelClass::securityResource();
            } catch (\Throwable $e) {
                $resource = [];
            }
        }

        $basePath = $basePath ?: ($resource['route_prefix'] ?? $this->defaultRoutePrefix($modelClass));
        $basePath = '/' . trim((string) $basePath, '/');

        return [
            'enabled' => true,
            'resource' => $modelClass,
            'route_prefix' => $resource['route_prefix'] ?? trim($basePath, '/'),
            'base_path' => $basePath,
            'record_id' => $recordId,
            'screen' => $screen,
            'actions' => $resource['actions'] ?? [],
            'auto_bind' => true,
            'endpoints' => [
                'resolve' => '/seguranca/operacao/resolve',
                'authorize' => '/seguranca/operacao/autorizar',
            ],
        ];
    }

    public function makeFromRequest(Request $request, string $modelClass, ?int $recordId = null, ?string $screen = null, ?string $basePath = null): array
    {
        return $this->make(
            $modelClass,
            $recordId,
            $screen ?: $this->inferScreen($request),
            $basePath ?: $this->inferBasePath($request)
        );
    }

    public function normalizeModelClass(string $modelClass): string
    {
        $modelClass = trim($modelClass);
        $modelClass = str_replace(['/', '|'], ['\\', '\\'], $modelClass);

        if ($modelClass === '') {
            return '';
        }

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        $candidate = 'App\\Models\\' . ltrim($modelClass, '\\');
        if (class_exists($candidate)) {
            return $candidate;
        }

        return $modelClass;
    }

    public function inferAction(Request $request, ?string $default = 'view'): string
    {
        $path = '/' . trim($request->path(), '/');
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $last = strtolower((string) end($segments));
        $method = strtoupper($request->method());

        if (in_array($last, ['delete', 'destroy', 'excluir'], true)) {
            return 'delete';
        }

        if (in_array($last, ['restore', 'restaurar'], true)) {
            return 'restore';
        }

        if (in_array($last, ['edit', 'editar'], true)) {
            return 'edit';
        }

        foreach ($segments as $segment) {
            $segment = strtolower($segment);
            if (in_array($segment, ['delete', 'destroy', 'excluir'], true)) {
                return 'delete';
            }
            if (in_array($segment, ['restore', 'restaurar'], true)) {
                return 'restore';
            }
            if (in_array($segment, ['edit', 'editar', 'update'], true)) {
                return 'edit';
            }
            if (in_array($segment, ['create', 'new', 'novo', 'store', 'save'], true)) {
                return $request->input('id') || $request->route('id') ? 'edit' : 'create';
            }
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return $request->input('id') || $request->route('id') ? 'edit' : 'create';
        }

        if ($method === 'DELETE') {
            return 'delete';
        }

        return $default ?: 'view';
    }

    public function inferRecordId(Request $request, ?string $recordParam = 'id'): ?int
    {
        $candidates = array_filter([
            $recordParam,
            'id',
            'record_id',
            'registro_id',
            'codigo',
        ]);

        foreach ($candidates as $key) {
            $value = $request->route($key);
            if ($value === null) {
                $value = $request->input($key);
            }

            if (is_object($value) && method_exists($value, 'getKey')) {
                $value = $value->getKey();
            }

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    protected function inferScreen(Request $request): string
    {
        $action = $this->inferAction($request, 'view');

        return match ($action) {
            'create' => 'create',
            'edit' => 'edit',
            'delete' => 'delete',
            'restore' => 'restore',
            default => 'list',
        };
    }

    protected function inferBasePath(Request $request): string
    {
        $segments = array_values(array_filter(explode('/', trim($request->path(), '/'))));
        if (!$segments) {
            return '/';
        }

        return '/' . $segments[0];
    }

    protected function defaultRoutePrefix(string $modelClass): string
    {
        $shortName = class_basename($modelClass);

        return Str::kebab(Str::pluralStudly($shortName));
    }
}
