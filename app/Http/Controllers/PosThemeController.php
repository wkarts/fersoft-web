<?php

namespace App\Http\Controllers;

use App\Models\UiPosThemePreset;
use App\Services\UiPosThemeService;
use App\Support\UiThemePalette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosThemeController extends Controller
{
    public function __construct(private UiPosThemeService $themes)
    {
        $this->middleware(function (Request $request, $next) {
            $user = $request->session()->get('user_logged');
            if (!is_array($user) || empty($user['id']) || empty($user['empresa'])) {
                return redirect('/login');
            }
            abort_unless(config('ui_theme.active', false), 404);
            abort_unless((bool) ($user['super'] ?? false), 403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $status = $this->themes->catalogStatus();
        $editing = null;
        if ($status['ready'] && $request->filled('edit_theme')) {
            $editing = UiPosThemePreset::query()->where('status', 'draft')->findOrFail((int) $request->query('edit_theme'));
        }
        return view('themes.refined.studio.pos', [
            'title' => 'Temas do PDV',
            'catalogStatus' => $status,
            'catalogReady' => $status['ready'],
            'builtinThemes' => $this->themes->builtinThemes(),
            'publishedThemes' => $this->themes->publishedThemes(),
            'customThemes' => $status['ready'] ? UiPosThemePreset::query()->orderByDesc('id')->limit(100)->get() : collect(),
            'editingTheme' => $editing,
            'defaultSlug' => $this->themes->defaultSlug(),
        ]);
    }

    private function requireCatalog(): void
    {
        abort_unless($this->themes->catalogStatus()['ready'], 503,
            'O catálogo do PDV está incompleto. Execute php artisan ui-themes:install --apply e confirme o diagnóstico.');
    }

    private function validatedTheme(Request $request): array
    {
        $rules = ['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:400']];
        foreach (UiPosThemeService::COLOR_KEYS as $key) {
            $rules[$key] = ['required', 'regex:/^#[a-fA-F0-9]{6}$/D'];
        }
        $data = $request->validate($rules);
        $colors = [];
        foreach (UiPosThemeService::COLOR_KEYS as $key) $colors[$key] = strtoupper($data[$key]);

        if (UiThemePalette::contrast($colors['surface'], $colors['text']) < 4.5
            || UiThemePalette::contrast($colors['surface'], $colors['muted']) < 4.5
            || UiThemePalette::contrast($colors['surface'], $colors['accent']) < 3.0
            || UiThemePalette::contrast($colors['surface'], $colors['accent_hover']) < 4.5) {
            throw ValidationException::withMessages([
                'text' => 'Aumente o contraste do texto, do texto auxiliar, da cor principal e do destaque com a superfície.',
            ]);
        }
        return [
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'colors' => $colors,
        ];
    }

    private function newSlug(string $name): string
    {
        return substr(Str::slug($name) ?: 'tema-pdv', 0, 65) . '-' . Str::lower(Str::random(8));
    }

    public function store(Request $request)
    {
        $this->requireCatalog();
        $data = $this->validatedTheme($request);
        UiPosThemePreset::query()->create(array_merge($data, [
            'slug' => $this->newSlug($data['name']),
            'status' => 'draft',
            'created_by' => (int) $request->session()->get('user_logged.id'),
        ]));
        return redirect()->route('config.pos-themes.index')->with('ui_pos_theme_feedback', 'Rascunho do PDV criado. Publique após conferir as cores.');
    }

    public function update(Request $request, UiPosThemePreset $preset)
    {
        $this->requireCatalog();
        abort_unless($preset->status === 'draft', 409, 'Duplique um modelo publicado para alterá-lo.');
        $data = $this->validatedTheme($request);
        $changed = UiPosThemePreset::query()->whereKey($preset->id)->where('status', 'draft')->update($data);
        abort_unless($changed === 1 || UiPosThemePreset::query()->whereKey($preset->id)->where('status', 'draft')->exists(), 409);
        return redirect()->route('config.pos-themes.index')->with('ui_pos_theme_feedback', 'Rascunho do PDV atualizado.');
    }

    public function duplicateBuiltin(string $slug)
    {
        $this->requireCatalog();
        $source = $this->themes->builtinThemes()[$slug] ?? null;
        abort_unless($source, 404);
        $this->duplicateDefinition($source);
        return redirect()->route('config.pos-themes.index')->with('ui_pos_theme_feedback', 'Cópia editável do modelo padrão criada.');
    }

    public function duplicate(UiPosThemePreset $preset)
    {
        $this->requireCatalog();
        $source = $this->themes->safeDefinition((array) $preset->colors, [
            'name' => (string) $preset->name, 'description' => (string) ($preset->description ?? ''),
        ]);
        $this->duplicateDefinition($source);
        return redirect()->route('config.pos-themes.index')->with('ui_pos_theme_feedback', 'Cópia editável criada.');
    }

    private function duplicateDefinition(array $source): void
    {
        $colors = [];
        foreach (UiPosThemeService::COLOR_KEYS as $key) $colors[$key] = $source[$key];
        UiPosThemePreset::query()->create([
            'slug' => $this->newSlug((string) $source['name']),
            'name' => $source['name'] . ' (cópia)',
            'description' => (string) ($source['description'] ?? ''),
            'colors' => $colors,
            'status' => 'draft',
            'created_by' => (int) session('user_logged.id'),
        ]);
    }

    public function publish(UiPosThemePreset $preset)
    {
        $this->requireCatalog();
        abort_unless($preset->status === 'draft', 409);
        abort_unless(!array_key_exists($preset->slug, $this->themes->builtinThemes()), 409);
        abort_unless(UiPosThemePreset::query()->whereKey($preset->id)->where('status', 'draft')->update([
            'status' => 'published', 'published_at' => now(),
        ]) === 1, 409);
        return redirect()->route('config.pos-themes.index')->with('ui_pos_theme_feedback', 'Modelo do PDV publicado.');
    }

    public function archive(UiPosThemePreset $preset)
    {
        $this->requireCatalog();
        abort_unless($preset->status === 'published', 409);
        abort_if($this->themes->defaultSlug() === $preset->slug, 409, 'Escolha outro padrão antes de arquivar este modelo.');
        abort_unless(UiPosThemePreset::query()->whereKey($preset->id)->where('status', 'published')->update([
            'status' => 'archived',
        ]) === 1, 409);
        return redirect()->route('config.pos-themes.index')->with('ui_pos_theme_feedback', 'Modelo do PDV arquivado.');
    }

    public function setDefault(Request $request)
    {
        $this->requireCatalog();
        $data = $request->validate(['theme_slug' => ['required', 'string', 'max:80']]);
        abort_unless(isset($this->themes->publishedThemes()[$data['theme_slug']]), 422, 'Publique o modelo antes de torná-lo padrão.');
        DB::table('ui_pos_theme_settings')->upsert([[
            'id' => 1,
            'default_theme_slug' => $data['theme_slug'],
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['id'], ['default_theme_slug', 'updated_at']);
        return redirect()->route('config.pos-themes.index')->with('ui_pos_theme_feedback', 'Padrão do PDV atualizado para todos os caixas.');
    }
}
