<?php

namespace App\Services;

use App\Models\UiPosThemePreset;
use App\Support\UiThemePalette;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Catálogo do caixa: nenhuma preferência pessoal ou padrão do ERP entra na resolução. */
class UiPosThemeService
{
    public const REQUIRED_COLUMNS = [
        'ui_pos_theme_presets' => ['id', 'slug', 'name', 'description', 'colors', 'status', 'created_by', 'published_at', 'created_at', 'updated_at'],
        'ui_pos_theme_settings' => ['id', 'default_theme_slug', 'created_at', 'updated_at'],
    ];

    public const REQUIRED_UNIQUE_INDEXES = [
        'ui_pos_theme_presets' => [['slug']],
        // id é chave primária; MySQL e SQLite podem não expô-la como índice único nomeado.
        'ui_pos_theme_settings' => [],
    ];

    public const COLOR_KEYS = ['accent', 'accent_hover', 'background', 'surface', 'text', 'border', 'muted'];

    public function builtinThemes(): array
    {
        return (array) config('ui_pos_theme.presets', []);
    }

    public function databaseReady(): bool
    {
        $cached = request()->attributes->get('ui_pos_theme.schema.ready');
        if (is_bool($cached)) return $cached;

        try {
            $ready = Schema::hasTable('ui_pos_theme_presets') && Schema::hasTable('ui_pos_theme_settings');
        } catch (\Throwable $e) {
            $ready = false;
        }
        request()->attributes->set('ui_pos_theme.schema.ready', $ready);
        return $ready;
    }

    public function catalogStatus(): array
    {
        $tables = [];
        $connectionError = false;
        try {
            foreach (self::REQUIRED_COLUMNS as $name => $required) {
                $exists = Schema::hasTable($name);
                $missing = $exists ? array_values(array_diff($required, Schema::getColumnListing($name))) : $required;
                $missingIndexes = [];
                if ($exists) {
                    foreach (self::REQUIRED_UNIQUE_INDEXES[$name] as $columns) {
                        if (!Schema::hasIndex($name, $columns, 'unique')) {
                            $missingIndexes[] = $columns;
                        }
                    }
                }
                $tables[$name] = compact('exists', 'missing', 'missingIndexes');
            }
        } catch (\Throwable $e) {
            $connectionError = true;
        }

        return [
            'ready' => !$connectionError && count($tables) === count(self::REQUIRED_COLUMNS)
                && !array_filter($tables, fn (array $table) => !$table['exists'] || $table['missing'] || $table['missingIndexes']),
            'tables' => $tables,
            'connection_error' => $connectionError,
        ];
    }

    public function publishedThemes(): array
    {
        $themes = $this->builtinThemes();
        if (!$this->databaseReady()) return $themes;

        try {
            foreach (UiPosThemePreset::query()->where('status', 'published')->get() as $record) {
                if (isset($themes[$record->slug])) continue; // Fábrica é imutável.
                $themes[$record->slug] = $this->safeDefinition((array) $record->colors, [
                    'name' => (string) $record->name,
                    'description' => (string) ($record->description ?? ''),
                ]);
            }
        } catch (\Throwable $e) {
            // Falha no catálogo não interrompe uma venda: padrão de fábrica permanece.
        }
        return $themes;
    }

    public function safeDefinition(array $colors, array $metadata = []): array
    {
        $default = $this->builtinThemes()[(string) config('ui_pos_theme.default_preset', 'pos-default')] ?? [];
        $definition = array_merge($default, $metadata);
        foreach (self::COLOR_KEYS as $key) {
            $candidate = $colors[$key] ?? null;
            if (is_string($candidate) && preg_match('/^#[A-Fa-f0-9]{6}$/D', $candidate)) {
                $definition[$key] = strtoupper($candidate);
            }
        }
        // Cores derivadas precisam acompanhar a paleta customizada, não o padrão.
        $surface = $definition['surface'] ?? '#FFFFFF';
        $ink = $definition['text'] ?? '#182F36';
        $accent = $definition['accent'] ?? '#1F9866';
        $definition['dark'] = UiThemePalette::luminance($definition['background'] ?? '#F5F7F7') < .19;
        $definition['surface_alt'] = UiThemePalette::mix($surface, $ink, .025);
        $definition['accent_soft'] = UiThemePalette::mix($surface, $accent, .09);
        return $definition;
    }

    public function defaultSlug(): string
    {
        $fallback = (string) config('ui_pos_theme.default_preset', 'pos-default');
        if (!$this->databaseReady()) return $fallback;

        try {
            $stored = DB::table('ui_pos_theme_settings')->where('id', 1)->value('default_theme_slug');
            return is_string($stored) && isset($this->publishedThemes()[$stored]) ? $stored : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    public function resolve(): array
    {
        $themes = $this->publishedThemes();
        $slug = $this->defaultSlug();
        $definition = $themes[$slug] ?? $themes['pos-default'] ?? [];
        return array_merge($definition, ['slug' => $slug]);
    }
}
