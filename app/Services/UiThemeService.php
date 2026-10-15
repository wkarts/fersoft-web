<?php

namespace App\Services;

use App\Models\UiThemePreset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UiThemeService
{
    private const COLOR_KEYS = [
        'accent', 'accent_hover', 'background', 'surface',
        'sidebar', 'sidebar_text', 'text', 'border',
    ];

    public function databaseReady(): bool
    {
        try {
            return Schema::hasTable('ui_theme_presets')
                && Schema::hasTable('ui_theme_company_settings')
                && Schema::hasTable('ui_theme_user_preferences');
        } catch (\Throwable $e) {
            // O tema em si não pode derrubar o ERP se o banco estiver indisponível.
            return false;
        }
    }

    public function builtinThemes(): array
    {
        return (array) config('ui_theme.presets', []);
    }

    public function publishedThemes(): array
    {
        $themes = $this->builtinThemes();

        if (!$this->databaseReady()) {
            return $themes;
        }

        try {
            foreach (UiThemePreset::query()->where('status', 'published')->get() as $record) {
                $colors = is_array($record->colors) ? $record->colors : [];
                $themes[$record->slug] = $this->safeDefinition($colors, [
                    'name' => (string) $record->name,
                    'description' => (string) ($record->description ?? ''),
                    'dark' => $record->sidebar_style === 'dark',
                    'density' => (string) $record->density,
                ]);
            }
        } catch (\Throwable $e) {
            // Uma falha no catálogo adicional não pode impedir renderizar o ERP.
        }

        return $themes;
    }

    public function companySettings(int $companyId): ?object
    {
        if ($companyId < 1 || !$this->databaseReady()) {
            return null;
        }

        try {
            return DB::table('ui_theme_company_settings')
                ->where('company_id', $companyId)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function availableForCompany(int $companyId, bool $super = false): array
    {
        $themes = $this->publishedThemes();

        if ($super) {
            return $themes;
        }

        $settings = $this->companySettings($companyId);
        $allowed = $settings && $settings->allowed_theme_slugs
            ? json_decode($settings->allowed_theme_slugs, true)
            : [];
        $allowed = is_array($allowed) ? $allowed : [];

        // Os três presets de fábrica sempre ficam disponíveis.
        return array_filter(
            $themes,
            fn ($theme, $slug) => array_key_exists($slug, $this->builtinThemes())
                || in_array($slug, $allowed, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function safeDefinition(array $colors, array $metadata = []): array
    {
        $base = $this->builtinThemes()['fer-light'] ?? [];
        $definition = array_merge($base, $metadata);

        foreach (self::COLOR_KEYS as $key) {
            $candidate = $colors[$key] ?? null;
            if (is_string($candidate) && preg_match('/^#[A-Fa-f0-9]{6}$/D', $candidate)) {
                $definition[$key] = strtoupper($candidate);
            }
        }

        $definition['dark'] = (bool) ($metadata['dark'] ?? false);
        $definition['density'] = in_array($metadata['density'] ?? '', ['compact', 'comfortable'], true)
            ? $metadata['density'] : 'comfortable';

        return $definition;
    }

    public function resolve(?bool $legacyDark = null): array
    {
        if (!config('ui_theme.active', false)) {
            return ['name' => 'classic', 'active' => false, 'dark' => false];
        }

        $request = request();
        $key = 'fersoft.ui_theme.' . ($legacyDark === null ? 'auto' : ($legacyDark ? 'dark' : 'light'));
        $cached = $request->attributes->get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $session = $request->session()->get('user_logged', []);
        $context = is_array($session) ? $session : [];
        $companyId = (int) ($context['empresa'] ?? 0);
        $userId = (int) ($context['id'] ?? 0);
        $super = (bool) ($context['super'] ?? false);
        $themes = $this->availableForCompany($companyId, $super);
        $settings = $this->companySettings($companyId);

        $fallbackSlug = (string) config('ui_theme.default_preset', 'fer-light');
        $slug = isset($themes[$settings->default_theme_slug ?? ''])
            ? $settings->default_theme_slug : $fallbackSlug;
        $colorMode = 'follow';
        $density = 'comfortable';

        if ($companyId > 0 && $userId > 0 && $this->databaseReady()) {
            try {
                $preference = DB::table('ui_theme_user_preferences')
                    ->where('company_id', $companyId)
                    ->where('user_id', $userId)
                    ->first();

                if ($preference) {
                    if (isset($themes[$preference->theme_slug])) {
                        $slug = $preference->theme_slug;
                    }
                    $colorMode = (string) $preference->color_mode;
                    $density = (string) $preference->density;
                }
            } catch (\Throwable $e) {
                // Falha opcional da preferência = tema refinado padrão.
            }
        }

        $definition = $themes[$slug] ?? $this->builtinThemes()[$fallbackSlug] ?? [];
        $themeDark = (bool) ($definition['dark'] ?? false);
        $dark = $colorMode === 'dark'
            ? true
            : ($colorMode === 'light' ? false : ($themeDark || (bool) $legacyDark));

        $resolved = array_merge($definition, [
            'slug' => $slug,
            'active' => true,
            'dark' => $dark,
            'color_mode' => $colorMode,
            'density' => in_array($density, ['compact', 'comfortable'], true) ? $density : 'comfortable',
        ]);
        $request->attributes->set($key, $resolved);

        return $resolved;
    }

    public function canSelect(int $companyId, string $slug, bool $super = false): bool
    {
        return array_key_exists($slug, $this->availableForCompany($companyId, $super));
    }
}
