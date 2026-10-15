<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\UiThemePreset;
use App\Services\UiThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UiThemeController extends Controller
{
    public function __construct(private UiThemeService $themes)
    {
        $this->middleware(function (Request $request, $next) {
            $session = $request->session()->get('user_logged');
            if (!is_array($session) || empty($session['id']) || empty($session['empresa'])) {
                return redirect('/login');
            }

            abort_unless(config('ui_theme.active', false), 404);
            return $next($request);
        });
    }

    private function context(): array
    {
        return (array) session('user_logged', []);
    }

    private function superOnly(): void
    {
        abort_unless((bool) ($this->context()['super'] ?? false), 403);
    }

    private function requireTables(): void
    {
        abort_unless(
            $this->themes->databaseReady(),
            503,
            'O catálogo visual ainda não foi instalado. Execute as migrations aditivas.'
        );
    }

    public function index()
    {
        $this->requireTables();
        $context = $this->context();
        $company = (int) $context['empresa'];
        $super = (bool) ($context['super'] ?? false);

        return view('themes.refined.studio.index', [
            'title' => 'Temas da interface',
            'availableThemes' => $this->themes->availableForCompany($company, $super),
            'builtinThemes' => $this->themes->builtinThemes(),
            'currentTheme' => $this->themes->resolve(),
            'customThemes' => $super
                ? UiThemePreset::query()->orderByDesc('id')->limit(100)->get()
                : collect(),
            'editingTheme' => $super && request()->filled('edit_theme')
                ? UiThemePreset::query()->where('status', 'draft')->findOrFail((int) request('edit_theme'))
                : null,
            // Empresas são pesquisadas sob demanda, sem carregar centenas na tela.
            'companies' => collect(),
            'companySettings' => $this->themes->companySettings($company),
            'isSuperadmin' => $super,
        ]);
    }

    public function select(Request $request)
    {
        $this->requireTables();
        $data = $request->validate([
            'theme_slug' => ['required', 'string', 'max:80'],
            'color_mode' => ['required', Rule::in(['follow', 'light', 'dark'])],
            'density' => ['required', Rule::in(['comfortable', 'compact'])],
        ]);

        $context = $this->context();
        $company = (int) $context['empresa'];
        $super = (bool) ($context['super'] ?? false);
        abort_unless($this->themes->canSelect($company, $data['theme_slug'], $super), 403);

        $now = now();
        DB::table('ui_theme_user_preferences')->updateOrInsert(
            ['company_id' => $company, 'user_id' => (int) $context['id']],
            [
                'theme_slug' => $data['theme_slug'],
                'color_mode' => $data['color_mode'],
                'density' => $data['density'],
                'updated_at' => $now,
            ]
        );

        return redirect()->route('ui.themes.index')
            ->with('ui_theme_feedback', 'Sua preferência visual foi salva para esta empresa.');
    }

    private function validatedThemeData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:400'],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'accent_hover' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'surface' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'sidebar' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'sidebar_text' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'text' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'border' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'sidebar_style' => ['required', Rule::in(['light', 'dark'])],
            'density' => ['required', Rule::in(['comfortable', 'compact'])],
        ]);

        // Não publicar temas cujo texto se torne ilegível nas áreas principais.
        if ($this->contrast($data['surface'], $data['text']) < 4.5) {
            throw ValidationException::withMessages([
                'text' => 'O texto precisa ter contraste suficiente com os cartões (mínimo 4,5:1).',
            ]);
        }
        if ($this->contrast($data['sidebar'], $data['sidebar_text']) < 4.5) {
            throw ValidationException::withMessages([
                'sidebar_text' => 'O texto precisa ter contraste suficiente com o menu (mínimo 4,5:1).',
            ]);
        }

        return $data;
    }

    private function contrast(string $background, string $foreground): float
    {
        $luminance = static function (string $hex): float {
            $channels = [];
            for ($offset = 1; $offset <= 5; $offset += 2) {
                $value = hexdec(substr($hex, $offset, 2)) / 255;
                $channels[] = $value <= .04045
                    ? $value / 12.92
                    : (($value + .055) / 1.055) ** 2.4;
            }
            return .2126 * $channels[0] + .7152 * $channels[1] + .0722 * $channels[2];
        };

        $light = max($luminance($background), $luminance($foreground));
        $dark = min($luminance($background), $luminance($foreground));
        return ($light + .05) / ($dark + .05);
    }

    public function store(Request $request)
    {
        $this->superOnly();
        $this->requireTables();

        $data = $this->validatedThemeData($request);

        $slug = Str::slug($data['name']);
        abort_unless(
            $slug !== '' && strlen($slug) <= 80
            && !array_key_exists($slug, $this->themes->builtinThemes())
            && !UiThemePreset::query()->where('slug', $slug)->exists(),
            422,
            'Já existe um tema com esse nome. Escolha outro.'
        );

        $colors = [];
        foreach (['accent', 'accent_hover', 'background', 'surface', 'sidebar',
                  'sidebar_text', 'text', 'border'] as $key) {
            $colors[$key] = strtoupper($data[$key]);
        }

        UiThemePreset::query()->create([
            'slug' => $slug,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'colors' => $colors,
            'sidebar_style' => $data['sidebar_style'],
            'density' => $data['density'],
            'status' => 'draft',
            'created_by' => (int) $this->context()['id'],
        ]);

        return redirect()->route('ui.themes.index')
            ->with('ui_theme_feedback', 'Tema criado como rascunho. Publique quando estiver pronto.');
    }

    public function update(Request $request, UiThemePreset $preset)
    {
        $this->superOnly();
        $this->requireTables();
        abort_unless($preset->status === 'draft', 403, 'Temas publicados não podem ser alterados.');

        $data = $this->validatedThemeData($request);
        $colors = [];
        foreach (['accent', 'accent_hover', 'background', 'surface', 'sidebar',
                  'sidebar_text', 'text', 'border'] as $key) {
            $colors[$key] = strtoupper($data[$key]);
        }
        $preset->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'colors' => $colors,
            'sidebar_style' => $data['sidebar_style'],
            'density' => $data['density'],
        ]);

        return redirect()->route('ui.themes.index')
            ->with('ui_theme_feedback', 'Rascunho atualizado sem modificar temas já publicados.');
    }

    public function duplicate(Request $request, UiThemePreset $preset)
    {
        $this->superOnly();
        $this->requireTables();

        UiThemePreset::query()->create([
            'slug' => substr($preset->slug, 0, 54) . '-copia-' . strtolower(Str::random(8)),
            'name' => Str::limit($preset->name . ' - Cópia', 120, ''),
            'description' => $preset->description,
            'colors' => $preset->colors,
            'sidebar_style' => $preset->sidebar_style,
            'density' => $preset->density,
            'status' => 'draft',
            'created_by' => (int) $this->context()['id'],
        ]);
        return redirect()->route('ui.themes.index')
            ->with('ui_theme_feedback', 'Nova versão em rascunho criada a partir do tema escolhido.');
    }

    public function archive(Request $request, UiThemePreset $preset)
    {
        $this->superOnly();
        $this->requireTables();
        abort_unless($preset->status === 'published', 422);

        $preset->update(['status' => 'archived']);

        return redirect()->route('ui.themes.index')
            ->with('ui_theme_feedback', 'Tema arquivado. Usuários passarão a utilizar o fallback autorizado.');
    }

    public function companySearch(Request $request)
    {
        $this->superOnly();
        $this->requireTables();

        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $search = trim($data['q']);
        $results = Empresa::query()
            ->select(['id', 'nome'])
            ->where(function ($query) use ($search) {
                $query->where('nome', 'like', '%' . $search . '%');
                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            })
            ->orderBy('nome')->limit(20)->get()
            ->map(fn ($company) => [
                'id' => (int) $company->id,
                'name' => (string) $company->nome,
            ]);

        return response()->json(['items' => $results]);
    }

    public function setDefault(Request $request)
    {
        $this->superOnly();
        $this->requireTables();
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:empresas,id'],
            'theme_slug' => ['required', 'string', 'max:80'],
        ]);
        $companyId = (int) $data['company_id'];
        abort_unless(
            $this->themes->canSelect($companyId, $data['theme_slug'], false),
            403,
            'Libere o tema à empresa antes de defini-lo como padrão.'
        );

        DB::table('ui_theme_company_settings')->updateOrInsert(
            ['company_id' => $companyId],
            ['default_theme_slug' => $data['theme_slug'], 'updated_at' => now()]
        );

        return redirect()->route('ui.themes.index')
            ->with('ui_theme_feedback', 'Tema padrão da empresa atualizado. Preferências individuais foram preservadas.');
    }

    public function publish(Request $request, UiThemePreset $preset)
    {
        $this->superOnly();
        $this->requireTables();

        if ($preset->status !== 'published') {
            $preset->update(['status' => 'published', 'published_at' => now()]);
        }

        return redirect()->route('ui.themes.index')
            ->with('ui_theme_feedback', 'Tema publicado. Agora você pode liberá-lo às empresas.');
    }

    public function grant(Request $request)
    {
        $this->superOnly();
        $this->requireTables();

        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:empresas,id'],
            'theme_slug' => ['required', 'string', 'max:80'],
        ]);

        $slug = $data['theme_slug'];
        abort_unless(array_key_exists($slug, $this->themes->publishedThemes()), 422);

        $companyId = (int) $data['company_id'];
        $settings = $this->themes->companySettings($companyId);
        $allowed = $settings ? json_decode((string) $settings->allowed_theme_slugs, true) : [];
        $allowed = is_array($allowed) ? $allowed : [];
        $allowed[] = $slug;

        DB::table('ui_theme_company_settings')->updateOrInsert(
            ['company_id' => $companyId],
            [
                'allowed_theme_slugs' => json_encode(array_values(array_unique($allowed))),
                'updated_at' => now(),
            ]
        );

        return redirect()->route('ui.themes.index')
            ->with('ui_theme_feedback', 'Tema disponibilizado à empresa selecionada.');
    }
}
