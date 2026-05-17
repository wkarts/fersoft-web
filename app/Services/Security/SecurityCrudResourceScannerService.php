<?php

namespace App\Services\Security;

use App\Models\BaseModel;
use App\Models\Security\SecurityCrudResource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class SecurityCrudResourceScannerService
{
    public function sync(): array
    {
        $stats = [
            'scanned' => 0,
            'synced' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($this->discoverModelClasses() as $class) {
            $stats['scanned']++;

            try {
                if (!$this->isValidBaseModel($class)) {
                    $stats['skipped']++;
                    continue;
                }

                $resource = $this->normalizeResource($class, $class::securityResource());

                SecurityCrudResource::updateOrCreate(
                    ['model_class' => $class],
                    $resource
                );

                $stats['synced']++;
            } catch (\Throwable $e) {
                $stats['errors'][] = [
                    'model' => $class,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    protected function discoverModelClasses(): array
    {
        $basePath = app_path('Models');
        $classes = [];

        foreach (File::allFiles($basePath) as $file) {
            $relative = Str::replaceFirst($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relative = str_replace(['/', DIRECTORY_SEPARATOR], '\\', $relative);
            $class = 'App\\Models\\' . Str::replaceLast('.php', '', $relative);

            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        sort($classes);
        return $classes;
    }

    protected function isValidBaseModel(string $class): bool
    {
        if ($class === BaseModel::class) {
            return false;
        }

        if (str_starts_with($class, 'App\\Models\\Security\\')) {
            return false;
        }

        if (!is_subclass_of($class, BaseModel::class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return false;
        }

        return method_exists($class, 'securityResource');
    }

    protected function normalizeResource(string $class, array $resource): array
    {
        $shortName = class_basename($class);
        $actions = $resource['actions'] ?? [];

        return [
            'module' => $resource['module'] ?? 'Geral',
            'technical_name' => $shortName,
            'display_name' => $resource['name'] ?? $shortName,
            'plural_display_name' => $resource['plural_name'] ?? Str::plural($resource['name'] ?? $shortName),
            'description' => $resource['description'] ?? null,
            'controller_class' => $resource['controller_class'] ?? null,
            'route_prefix' => $resource['route_prefix'] ?? null,
            'table_name' => $resource['table_name'] ?? null,
            'icon' => $resource['icon'] ?? 'default',
            'sensitive' => (bool) ($resource['sensitive'] ?? false),
            'tenant_visible' => (bool) ($resource['tenant_visible'] ?? true),
            'super_admin_only' => (bool) ($resource['super_admin_only'] ?? false),
            'base_model_detected' => true,
            'base_controller_detected' => (bool) ($resource['base_controller_detected'] ?? false),
            'source' => $resource['source'] ?? 'model',
            'actions' => $actions,
            'enabled' => (bool) ($resource['enabled'] ?? true),
        ];
    }
}
