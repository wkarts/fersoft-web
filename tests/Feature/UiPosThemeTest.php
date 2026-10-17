<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificaEmpresa;
use App\Services\UiPosThemeService;
use App\Support\UiThemePalette;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Banco SQLite isolado: não acessa homologação. */
class UiPosThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array', 'ui_theme.active' => true]);
        DB::purge('sqlite');
        Schema::clearResolvedInstance('db.schema');
    }

    private function installPosTables(): void
    {
        (require database_path('migrations/2026_10_16_090000_create_ui_pos_theme_presets_table.php'))->up();
        (require database_path('migrations/2026_10_16_090001_create_ui_pos_theme_settings_table.php'))->up();
    }

    public function test_pdv_keeps_current_palette_when_catalog_is_not_installed_and_erp_theme_changes(): void
    {
        config(['ui_theme.default_preset' => 'fer-night']);
        $theme = app(UiPosThemeService::class)->resolve();

        $this->assertSame('pos-default', $theme['slug']);
        $this->assertSame('#F5F7F7', $theme['background']);
        $this->assertSame('#FFFFFF', $theme['surface']);
        $this->assertSame('#1F9866', $theme['accent']);
        $this->assertFalse($theme['dark']);
        $this->assertSame('#F5F7F7', UiThemePalette::tokens($theme)['--ui-bg']);
        $this->assertFalse(Schema::hasTable('ui_pos_theme_presets'));
    }

    public function test_only_published_pos_theme_can_replace_global_pdv_default(): void
    {
        $this->installPosTables();
        $this->assertTrue(app(UiPosThemeService::class)->catalogStatus()['ready']);

        DB::table('ui_pos_theme_presets')->insert([
            'slug' => 'pos-meu-caixa', 'name' => 'Meu caixa', 'description' => '',
            'colors' => json_encode([
                'accent' => '#167D5B', 'accent_hover' => '#11694B',
                'background' => '#F2F7F4', 'surface' => '#FFFFFF',
                'text' => '#19362D', 'border' => '#D7E7DC', 'muted' => '#5F766A',
            ]),
            'status' => 'published', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('ui_pos_theme_settings')->insert([
            'id' => 1, 'default_theme_slug' => 'pos-meu-caixa', 'created_at' => now(), 'updated_at' => now(),
        ]);
        config(['ui_theme.default_preset' => 'fer-night']);
        $this->assertSame('pos-meu-caixa', app(UiPosThemeService::class)->resolve()['slug']);
        $this->assertSame('#F2F7F4', app(UiPosThemeService::class)->resolve()['background']);

        DB::table('ui_pos_theme_presets')->where('slug', 'pos-meu-caixa')->update(['status' => 'archived']);
        $this->assertSame('pos-default', app(UiPosThemeService::class)->resolve()['slug']);
    }

    public function test_partial_catalog_still_renders_the_original_pdv_palette(): void
    {
        (require database_path('migrations/2026_10_16_090000_create_ui_pos_theme_presets_table.php'))->up();
        $this->assertFalse(app(UiPosThemeService::class)->catalogStatus()['ready']);
        $this->assertSame('pos-default', app(UiPosThemeService::class)->resolve()['slug']);
    }

    public function test_builtin_palettes_keep_readable_text_and_action_contrast(): void
    {
        foreach (app(UiPosThemeService::class)->builtinThemes() as $theme) {
            $this->assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($theme['surface'], $theme['text']));
            $this->assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($theme['surface'], $theme['muted']));
            $this->assertGreaterThanOrEqual(3.0, UiThemePalette::contrast($theme['surface'], $theme['accent']));
            $this->assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($theme['surface'], $theme['accent_hover']));
            $this->assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($theme['accent'], UiThemePalette::foreground($theme['accent'])));
            $this->assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($theme['accent_hover'], UiThemePalette::foreground($theme['accent_hover'])));
        }
    }

    public function test_super_can_create_publish_and_choose_a_separate_pdv_default(): void
    {
        $this->installPosTables();
        $this->withoutMiddleware(VerificaEmpresa::class)
            ->withSession(['user_logged' => ['id' => 4, 'empresa' => 1, 'super' => 1]]);

        $this->post('/config/temas/pdv/modelos', [
            'name' => 'Caixa próprio', 'description' => 'Cores para caixa',
            'accent' => '#167D5B', 'accent_hover' => '#11694B',
            'background' => '#F2F7F4', 'surface' => '#FFFFFF',
            'text' => '#19362D', 'border' => '#D7E7DC', 'muted' => '#5F766A',
        ])->assertRedirect(route('config.pos-themes.index'));
        $preset = DB::table('ui_pos_theme_presets')->first();
        $this->assertSame('draft', $preset->status);
        $this->postJson('/config/temas/pdv/padrao', ['theme_slug' => $preset->slug])->assertStatus(422);
        $this->post("/config/temas/pdv/modelos/{$preset->id}/publicar")
            ->assertRedirect(route('config.pos-themes.index'));
        $this->post('/config/temas/pdv/padrao', ['theme_slug' => $preset->slug])
            ->assertRedirect(route('config.pos-themes.index'));
        $this->assertSame($preset->slug, app(UiPosThemeService::class)->resolve()['slug']);
        $this->postJson("/config/temas/pdv/modelos/{$preset->id}/arquivar")->assertStatus(409);
    }

    public function test_super_permissions_and_routes_are_separate_from_erp_theme_studio(): void
    {
        foreach (['config.pos-themes.index', 'config.pos-themes.store', 'config.pos-themes.update',
            'config.pos-themes.duplicate', 'config.pos-themes.duplicate-builtin',
            'config.pos-themes.publish', 'config.pos-themes.archive', 'config.pos-themes.default'] as $name) {
            $this->assertTrue(Route::has($name), "Rota ausente: {$name}");
            $this->assertContains('verificaEmpresa', Route::getRoutes()->getByName($name)->gatherMiddleware());
        }
        $this->withoutMiddleware(VerificaEmpresa::class)
            ->withSession(['user_logged' => ['id' => 11, 'empresa' => 1, 'super' => 0]])
            ->getJson('/config/temas/pdv')
            ->assertForbidden();
    }

    public function test_four_standalone_pdv_views_use_independent_palette_without_changing_sale_controls(): void
    {
        foreach (['main', 'main2', 'main3', 'pdv_assincrono'] as $view) {
            $content = file_get_contents(resource_path("views/frontBox/{$view}.blade.php"));
            $this->assertStringContainsString('UiPosThemeService::class', $content, $view);
            $this->assertStringContainsString('themes.refined.pos-assets', $content, $view);
            $this->assertStringContainsString('ui-pos.css', $content, $view);
            $this->assertStringNotContainsString('UiThemeService::class', $content, $view);
        }
        $this->assertStringContainsString('UiThemePalette::tokens', file_get_contents(resource_path('views/themes/refined/pos-assets.blade.php')));
    }
}
