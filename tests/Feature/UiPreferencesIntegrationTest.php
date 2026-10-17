<?php

namespace Tests\Feature;

use App\Http\Controllers\UiThemeController;
use App\Models\UiThemePreset;
use App\Services\UiThemeService;
use App\Support\UiThemePalette;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

/** Real controller, model and SQL. Never connects to a customer's database. */
final class UiPreferencesIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array', 'ui_theme.active' => true]);
        DB::purge('sqlite');
        Schema::clearResolvedInstance('db.schema');
        foreach ([
            '2026_10_14_230100_create_ui_theme_presets_table.php',
            '2026_10_14_230101_create_ui_theme_company_settings_table.php',
            '2026_10_14_230102_create_ui_theme_user_preferences_table.php',
            '2026_10_15_060000_add_menu_mode_to_ui_theme_user_preferences_table.php',
            '2026_10_16_050000_create_ui_theme_system_settings_table.php',
        ] as $migration) {
            (require database_path('migrations/' . $migration))->up();
        }
        Schema::create('empresas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome');
        });
        DB::table('empresas')->insert([['id' => 1, 'nome' => 'Empresa A'], ['id' => 2, 'nome' => 'Empresa B']]);
    }

    private function requestFor(int $company, int $user, bool $super = false, array $values = []): Request
    {
        $session = app('session')->driver();
        $session->put('user_logged', ['id' => $user, 'empresa' => $company, 'super' => $super ? 1 : 0]);
        $request = Request::create('/ui/temas', 'POST', $values);
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);
        return $request;
    }

    private function select(int $company, int $user, string $theme, string $menu = 'follow'): void
    {
        $request = $this->requestFor($company, $user, false, [
            'theme_slug' => $theme, 'menu_mode' => $menu, 'density' => 'compact', 'color_mode' => 'follow',
            'company_id' => 9999, 'user_id' => 9999,
        ]);
        app(UiThemeController::class)->select($request);
    }

    private function resolved(int $company, int $user, ?bool $oldDark = null): array
    {
        $this->requestFor($company, $user);
        return app(UiThemeService::class)->resolve($oldDark);
    }

    private function custom(): UiThemePreset
    {
        return UiThemePreset::query()->create([
            'slug' => 'cliente-especial', 'name' => 'Cliente especial',
            'colors' => config('ui_theme.presets.fer-slate'), 'status' => 'published',
            'density' => 'compact', 'sidebar_style' => 'dark', 'published_at' => now(), 'created_by' => 1,
        ]);
    }

    private function forbidden(callable $operation): void
    {
        try {
            $operation();
            $this->fail('The unauthorized operation was accepted.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_unselected_theme_matches_approved_light_workspace_and_sidebar_reference(): void
    {
        $resolved = $this->resolved(1, 11);
        $tokens = UiThemePalette::tokens($resolved);

        $this->assertSame('fer-light', $resolved['slug']);
        $this->assertFalse($resolved['dark']);
        $this->assertSame('#F5F7F7', $tokens['--ui-bg']);
        $this->assertSame('#FFFFFF', $tokens['--ui-aside']);
        $this->assertSame('#1F9866', $tokens['--ui-accent']);
        $this->assertSame('#182F36', $tokens['--ui-ink']);
        $this->assertGreaterThanOrEqual(
            4.5,
            UiThemePalette::contrast($tokens['--ui-aside'], $tokens['--ui-sidebar-ink'])
        );
    }

    public function test_approved_sidebar_color_density_and_navigation_combinations_are_saved_without_new_schema(): void
    {
        foreach (['light', 'dark'] as $color) {
            foreach (['light', 'dark'] as $sidebar) {
                foreach (['comfortable', 'compact'] as $density) {
                    foreach (['lateral', 'superior'] as $navigation) {
                        $request = $this->requestFor(1, 11, false, [
                            'theme_slug' => 'fer-light',
                            'color_mode' => $color,
                            'sidebar_mode' => $sidebar,
                            'density' => $density,
                            'menu_mode' => $navigation,
                        ]);
                        app(UiThemeController::class)->select($request);

                        $resolved = $this->resolved(1, 11);
                        $tokens = UiThemePalette::tokens($resolved);
                        $this->assertSame($sidebar === 'dark' ? 'fer-slate' : 'fer-light', $resolved['slug']);
                        $this->assertSame($color === 'dark', $resolved['dark']);
                        $this->assertSame($density, $resolved['density']);
                        $this->assertSame($navigation, $resolved['menu_mode']);
                        $this->assertSame($color === 'dark' ? '#11252C' : '#F5F7F7', $tokens['--ui-bg']);
                        $this->assertSame(
                            $color === 'dark' ? '#172D35' : ($sidebar === 'dark' ? '#1D2639' : '#FFFFFF'),
                            $tokens['--ui-aside']
                        );
                        $this->assertSame($color === 'dark' ? '#62D3AB' : '#1F9866', $tokens['--ui-accent']);
                    }
                }
            }
        }

        $this->assertSame(1, DB::table('ui_theme_user_preferences')->count());
    }

    public function test_personal_preferences_are_scoped_to_company_and_user(): void
    {
        $this->select(1, 11, 'fer-night', 'superior');
        $this->select(2, 11, 'fer-light', 'lateral');
        $this->select(1, 12, 'fer-slate');
        $this->assertSame('fer-night', $this->resolved(1, 11)['slug']);
        $this->assertSame('superior', $this->resolved(1, 11)['menu_mode']);
        $this->assertSame('fer-light', $this->resolved(2, 11)['slug']);
        $this->assertSame('fer-slate', $this->resolved(1, 12)['slug']);
        $this->assertSame(3, DB::table('ui_theme_user_preferences')->count());
        $this->assertFalse(DB::table('ui_theme_user_preferences')->where('company_id', 9999)->exists());
    }

    public function test_explicit_theme_and_request_cache_do_not_leak_previous_tenant_or_legacy_dark(): void
    {
        $this->select(1, 11, 'fer-light');
        $this->select(2, 11, 'fer-night');
        $request = $this->requestFor(1, 11);
        $service = app(UiThemeService::class);
        $this->assertFalse($service->resolve(true)['dark'], 'Selected light theme must not inherit legacy dark preference.');
        $request->session()->put('user_logged', ['id' => 11, 'empresa' => 2, 'super' => 0]);
        $this->assertTrue($service->resolve(true)['dark']);
        $this->assertSame('fer-night', $service->resolve(true)['slug']);
    }

    public function test_only_superadmin_publishes_and_grants_custom_themes(): void
    {
        $preset = $this->custom();
        $ordinary = $this->requestFor(1, 11);
        $this->forbidden(fn () => app(UiThemeController::class)->publish($ordinary, $preset));
        $this->forbidden(fn () => $this->select(1, 11, $preset->slug));
        $grant = $this->requestFor(1, 1, true, ['company_id' => 1, 'theme_slug' => $preset->slug]);
        app(UiThemeController::class)->grant($grant);
        $this->select(1, 11, $preset->slug);
        $this->assertSame($preset->slug, $this->resolved(1, 11)['slug']);
        $this->forbidden(fn () => $this->select(2, 11, $preset->slug));
        $this->assertSame('fer-light', $this->resolved(2, 12)['slug']);
    }

    public function test_company_default_and_reset_preserve_other_personal_settings(): void
    {
        $request = $this->requestFor(1, 1, true, ['company_id' => 1, 'theme_slug' => 'fer-slate']);
        app(UiThemeController::class)->setDefault($request);
        $this->assertSame('fer-slate', $this->resolved(1, 11)['slug']);
        $this->select(1, 11, 'fer-light');
        $this->select(2, 11, 'fer-night');
        $request = $this->requestFor(1, 11);
        app(UiThemeController::class)->reset($request);
        $this->assertSame('fer-slate', $this->resolved(1, 11)['slug']);
        $this->assertSame('fer-night', $this->resolved(2, 11)['slug']);
    }

    public function test_archive_falls_back_without_corrupting_preferences(): void
    {
        $preset = $this->custom();
        $request = $this->requestFor(1, 1, true, ['company_id' => 1, 'theme_slug' => $preset->slug]);
        app(UiThemeController::class)->grant($request);
        $this->select(1, 11, $preset->slug);
        $request = $this->requestFor(1, 1, true);
        app(UiThemeController::class)->archive($request, $preset);
        $this->assertSame('fer-light', $this->resolved(1, 11)['slug']);
        $this->assertSame($preset->slug, DB::table('ui_theme_user_preferences')->where('user_id', 11)->value('theme_slug'));
    }

    public function test_published_theme_cannot_be_edited_and_duplicate_is_a_private_draft(): void
    {
        $preset = $this->custom();
        $request = $this->requestFor(1, 1, true);
        $this->forbidden(fn () => app(UiThemeController::class)->update($request, $preset));
        app(UiThemeController::class)->duplicate($request, $preset);
        $copy = UiThemePreset::query()->where('id', '!=', $preset->id)->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertNotSame($copy->slug, $preset->slug);
        $this->requestFor(1, 1, true);
        $this->assertArrayNotHasKey($copy->slug, app(UiThemeService::class)->publishedThemes());
    }

    public function test_classic_ignores_saved_preferences_without_database_queries(): void
    {
        $this->select(1, 11, 'fer-night');
        $this->requestFor(1, 11);
        config(['ui_theme.active' => false]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $resolved = app(UiThemeService::class)->resolve(true);
        $this->assertFalse($resolved['active']);
        $this->assertSame('classic', $resolved['name']);
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_superadmin_updates_factory_palette_without_changing_slug_and_can_restore_it(): void
    {
        $colors = config('ui_theme.presets.fer-light');
        $colors['accent'] = '#176B50';
        $request = $this->requestFor(1, 1, true, array_merge(array_intersect_key($colors,
            array_flip(['accent', 'accent_hover', 'background', 'surface', 'sidebar',
                'sidebar_text', 'text', 'border'])), [
            'name' => 'Claro operacional', 'description' => 'Paleta oficial ajustada',
            'sidebar_style' => 'light', 'density' => 'comfortable',
        ]));
        app(UiThemeController::class)->updateFactory($request, 'fer-light');
        $this->assertSame('standard', UiThemePreset::query()->where('slug', 'fer-light')->value('status'));
        $this->assertSame('Claro operacional', $this->resolved(2, 20)['name']);
        $this->assertSame('#176B50', $this->resolved(2, 20)['accent']);

        $ordinary = $this->requestFor(1, 11, false, $request->all());
        $this->forbidden(fn () => app(UiThemeController::class)->restoreFactory($ordinary, 'fer-light'));
        $super = $this->requestFor(1, 1, true);
        app(UiThemeController::class)->restoreFactory($super, 'fer-light');
        $this->assertSame('archived', UiThemePreset::query()->where('slug', 'fer-light')->value('status'));
        $this->assertSame('Claro', $this->resolved(2, 20)['name']);
        $this->assertSame('#1F9866', $this->resolved(2, 20)['accent']);
    }

    public function test_public_standard_and_global_default_respect_company_and_personal_overrides(): void
    {
        $preset = $this->custom();
        $ordinary = $this->requestFor(1, 11);
        $this->forbidden(fn () => app(UiThemeController::class)->promote($ordinary, $preset));
        $super = $this->requestFor(1, 1, true);
        app(UiThemeController::class)->promote($super, $preset);
        $this->requestFor(2, 22);
        $this->assertTrue(app(UiThemeService::class)->canSelect(2, $preset->slug));

        $super = $this->requestFor(1, 1, true, ['theme_slug' => $preset->slug]);
        app(UiThemeController::class)->setGlobalDefault($super);
        $this->assertSame($preset->slug, $this->resolved(2, 22)['slug']);

        $super = $this->requestFor(1, 1, true, ['company_id' => 1, 'theme_slug' => 'fer-night']);
        app(UiThemeController::class)->setDefault($super);
        $this->assertSame('fer-night', $this->resolved(1, 22)['slug']);
        $this->select(1, 11, 'fer-light');
        $this->assertSame('fer-light', $this->resolved(1, 11)['slug']);

        $super = $this->requestFor(1, 1, true);
        app(UiThemeController::class)->demote($super, $preset);
        $this->assertFalse(app(UiThemeService::class)->canSelect(2, $preset->slug));
        $this->assertSame('fer-light', $this->resolved(2, 22)['slug']);
        $this->assertSame('fer-night', $this->resolved(1, 22)['slug']);
        $this->assertSame('fer-light', $this->resolved(1, 11)['slug']);
    }

    public function test_missing_optional_global_table_does_not_disable_installed_catalog(): void
    {
        Schema::drop('ui_theme_system_settings');
        $this->requestFor(1, 11);
        $service = app(UiThemeService::class);
        $this->assertTrue($service->catalogStatus()['ready']);
        $this->assertSame('fer-light', $service->resolve()['slug']);
        $this->assertFalse($service->globalSettingsReady());
    }
}

