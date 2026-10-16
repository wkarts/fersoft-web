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
        $request = request();
        $cached = $request->attributes->get('ui_theme.schema.ready');
        if (is_bool($cached)) {
            return $cached;
        }

        try {
            $ready = Schema::hasTable('ui_theme_presets')
                && Schema::hasTable('ui_theme_company_settings')
                && Schema::hasTable('ui_theme_user_preferences');
        } catch (\Throwable $e) {
            // O tema em si não pode derrubar o ERP se o banco estiver indisponível.
            $ready = false;
        }

        // Cache somente na requisição, nunca globalmente entre empresas.
        $request->attributes->set('ui_theme.schema.ready', $ready);
        return $ready;
    }

    public function builtinThemes(): array
    {
        return (array) config('ui_theme.presets', []);
    }

    public function publishedThemes(): array
    {
        $request = request();
        $cached = $request->attributes->get('ui_theme.published');
        if (is_array($cached)) {
            return $cached;
        }

        $themes = $this->builtinThemes();

        if (!$this->databaseReady()) {
            $request->attributes->set('ui_theme.published', $themes);
            return $themes;
        }

        try {
            foreach (UiThemePreset::query()->where('status', 'published')->get() as $record) {
                // Presets de fábrica jamais podem ser sobrescritos pelo catálogo SQL.
                if (array_key_exists($record->slug, $this->builtinThemes())) {
                    continue;
                }
                $colors = is_array($record->colors) ? $record->colors : [];
                $themes[$record->slug] = $this->safeDefinition($colors, [
                    'name' => (string) $record->name,
                    'description' => (string) ($record->description ?? ''),
                    // Sidebar escura não implica modo escuro da página.
                    // O contraste de fundo do modelo determina o modo sugerido.
                    'dark' => $this->isDarkBackground((string) ($colors['background'] ?? '#FFFFFF')),
                    'density' => (string) $record->density,
                ]);
            }
        } catch (\Throwable $e) {
            // Uma falha no catálogo adicional não pode impedir renderizar o ERP.
        }

        $request->attributes->set('ui_theme.published', $themes);
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
        // Tokens auxiliares fixos pertencem apenas aos presets oficiais.
        // Presets criados no Studio derivam essas cores de sua própria paleta.
        foreach (['surface_alt', 'muted', 'accent_soft', 'hover',
                  'nav_active_bg', 'nav_active_text'] as $key) {
            unset($base[$key]);
        }
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

    private function isDarkBackground(string $hex): bool
    {
        if (!preg_match('/^#[A-Fa-f0-9]{6}$/D', $hex)) {
            return false;
        }
        $rgb = [
            hexdec(substr($hex, 1, 2)),
            hexdec(substr($hex, 3, 2)),
            hexdec(substr($hex, 5, 2)),
        ];
        return (0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2]) < 125;
    }

    public function resolve(?bool $legacyDark = null): array
    {
        if (!config('ui_theme.active', false)) {
            return ['name' => 'classic', 'active' => false, 'dark' => false];
        }

        $request = request();
        $session = $request->hasSession() ? $request->session()->get('user_logged', []) : [];
        $context = is_array($session) ? $session : [];
        $companyId = (int) ($context['empresa'] ?? 0);
        $userId = (int) ($context['id'] ?? 0);
        $super = (bool) ($context['super'] ?? false);
        // Scope cached values to company, user, privilege and requested color mode.
        $key = 'fersoft.ui_theme.' . $companyId . ':' . $userId . ':' . (int) $super
            . ':' . ($legacyDark === null ? 'auto' : ($legacyDark ? 'dark' : 'light'));
        $cached = $request->attributes->get($key);
        if (is_array($cached)) {
            return $cached;
        }
        $themes = $this->availableForCompany($companyId, $super);
        $settings = $this->companySettings($companyId);

        $fallbackSlug = (string) config('ui_theme.default_preset', 'fer-light');
        $slug = isset($themes[$settings->default_theme_slug ?? ''])
            ? $settings->default_theme_slug : $fallbackSlug;
        $hasPersonalChoice = false;
        $colorMode = 'follow';
        $menuMode = 'follow';
        $density = null; // Sem preferência pessoal, respeitar densidade do tema.

        if ($companyId > 0 && $userId > 0 && $this->databaseReady()) {
            try {
                $preference = DB::table('ui_theme_user_preferences')
                    ->where('company_id', $companyId)
                    ->where('user_id', $userId)
                    ->first();

                if ($preference) {
                    if (isset($themes[$preference->theme_slug])) {
                        $slug = $preference->theme_slug;
                        $hasPersonalChoice = true;
                    }
                    $colorMode = (string) $preference->color_mode;
                    $density = (string) $preference->density;
                    $menuMode = (string) ($preference->menu_mode ?? 'follow');
                }
            } catch (\Throwable $e) {
                // Falha opcional da preferência = tema refinado padrão.
            }
        }

        $definition = $themes[$slug] ?? $this->builtinThemes()[$fallbackSlug] ?? [];
        $themeDark = (bool) ($definition['dark'] ?? false);
        $dark = $colorMode === 'dark'
            ? true
            : ($colorMode === 'light' ? false
                : ($hasPersonalChoice ? $themeDark : ($themeDark || (bool) $legacyDark)));

        // Uma preferência de modo claro/escuro adapta o conjunto de superfícies,
        // nunca aplica texto escuro sobre fundo escuro nem vice-versa.
        if ($dark !== $themeDark) {
            $fallbackPalette = $this->builtinThemes()[$dark ? 'fer-night' : 'fer-light'] ?? [];
            $keys = ['background', 'surface', 'sidebar', 'sidebar_text', 'text', 'border'];
            if (array_key_exists($slug, $this->builtinThemes())) {
                // Os controles claro/escuro do HTML mudam também acento e
                // tokens de componentes nos presets de fábrica.
                $keys = array_merge($keys, [
                    'accent', 'accent_hover', 'surface_alt', 'muted',
                    'accent_soft', 'hover', 'nav_active_bg', 'nav_active_text',
                ]);
            }
            foreach ($keys as $key) {
                $definition[$key] = $fallbackPalette[$key] ?? $definition[$key] ?? '';
            }
        }

        $resolved = array_merge($definition, [
            'slug' => $slug,
            'active' => true,
            'dark' => $dark,
            'color_mode' => $colorMode,
            'menu_mode' => in_array($menuMode, ['lateral', 'superior'], true) ? $menuMode : 'follow',
            'density' => in_array($density, ['compact', 'comfortable'], true)
                ? $density
                : (in_array($definition['density'] ?? '', ['compact', 'comfortable'], true)
                    ? $definition['density'] : 'comfortable'),
        ]);
        $request->attributes->set($key, $resolved);

        return $resolved;
    }

    public function canSelect(int $companyId, string $slug, bool $super = false): bool
    {
        return array_key_exists($slug, $this->availableForCompany($companyId, $super));
    }
}

