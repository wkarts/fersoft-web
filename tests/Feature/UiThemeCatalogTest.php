<?php

namespace Tests\Feature;

use App\Services\UiThemeService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UiThemeCatalogTest extends TestCase
{
    public function test_tema_aprovado_e_padrao_e_classico_continua_como_rollback(): void
    {
        if (!env('APP_UI_THEME')) {
            $this->assertSame('refined', config('ui_theme.name'));
            $this->assertTrue(config('ui_theme.active'));
        }

        config()->set('ui_theme.active', false);
        $resolved = app(UiThemeService::class)->resolve();

        $this->assertSame('classic', $resolved['name']);
        $this->assertFalse($resolved['active']);
    }

    public function test_tres_presets_profissionais_estao_disponiveis_sem_migrations(): void
    {
        $presets = app(UiThemeService::class)->builtinThemes();

        $this->assertArrayHasKey('fer-light', $presets);
        $this->assertArrayHasKey('fer-slate', $presets);
        $this->assertArrayHasKey('fer-night', $presets);
        $this->assertSame('#1D2639', $presets['fer-slate']['sidebar']);
        $this->assertFalse($presets['fer-slate']['dark']);
        $this->assertTrue($presets['fer-night']['dark']);
    }

    public function test_tokens_administraveis_nao_aceitam_css_arbitrario(): void
    {
        $service = app(UiThemeService::class);

        $tokens = $service->safeDefinition([
            'accent' => '#123ABC',
            'background' => 'url(javascript:alert(1))',
            'surface' => '#abcdef',
            'custom' => 'position:fixed',
        ], [
            'name' => 'Personalizado',
            'density' => 'ignored',
        ]);

        $this->assertSame('#123ABC', $tokens['accent']);
        $this->assertSame('#F5F7F7', strtoupper($tokens['background']));
        $this->assertSame('#ABCDEF', $tokens['surface']);
        $this->assertArrayNotHasKey('custom', $tokens);
        $this->assertArrayNotHasKey('nav_active_bg', $tokens);
        $this->assertArrayNotHasKey('surface_alt', $tokens);
        $this->assertSame('comfortable', $tokens['density']);
    }

    public function test_rotas_de_temas_sao_aditivas_e_herdam_protecao_da_empresa(): void
    {
        foreach ([
            'ui.themes.index',
            'ui.themes.select',
            'ui.themes.store',
            'ui.themes.update',
            'ui.themes.duplicate',
            'ui.themes.publish',
            'ui.themes.archive',
            'ui.themes.company.search',
            'ui.themes.grant',
            'ui.themes.company.default',
        ] as $name) {
            $this->assertTrue(Route::has($name), "Rota ausente: {$name}");
            $route = Route::getRoutes()->getByName($name);
            $this->assertContains('verificaEmpresa', $route->gatherMiddleware());
            [$controller, $method] = explode('@', $route->getActionName(), 2);
            $this->assertTrue(class_exists($controller), "Controller ausente: {$controller}");
            $this->assertTrue(method_exists($controller, $method), "Método ausente: {$controller}@{$method}");
        }
    }

    public function test_shell_nao_substitui_menu_ou_eventos_do_erp(): void
    {
        foreach (['menu_lateral', 'menu_superior'] as $type) {
            $markup = file_get_contents(resource_path("views/default/{$type}.blade.php"));
            $this->assertStringContainsString("config('ui_theme.active', false)", $markup);
            $this->assertStringContainsString("themes.refined.workbench", $markup);
            $this->assertStringContainsString("themes.refined.assets", $markup);
            $this->assertStringContainsString('UiThemeService::class', $markup);
            $this->assertStringContainsString("uiResolvedTheme['dark']", $markup);
            $this->assertStringContainsString("uiResolvedTheme['density']", $markup);
            $this->assertStringContainsString('id="kt_content"', $markup);
            $this->assertStringContainsString('id="kt_header"', $markup);
        }

        $workbench = file_get_contents(resource_path('views/themes/refined/workbench.blade.php'));
        $this->assertStringContainsString("!isset(\$group['ativo'])", $workbench);
        $this->assertStringContainsString("!isset(\$item['rota_ativa'])", $workbench);
        $this->assertStringContainsString('ui-command-palette', $workbench);
    }

    public function test_migrations_separam_tabelas_sem_drop(): void
    {
        foreach ([
            '2026_10_14_230100_create_ui_theme_presets_table.php',
            '2026_10_14_230101_create_ui_theme_company_settings_table.php',
            '2026_10_14_230102_create_ui_theme_user_preferences_table.php',
        ] as $filename) {
            $source = file_get_contents(database_path('migrations/' . $filename));
            $this->assertStringContainsString('public function up()', $source);
            $this->assertStringContainsString('public function down()', $source);
            $this->assertStringNotContainsString('Schema::drop', $source);
            $this->assertStringNotContainsString('DB::statement', $source);
        }
    }
}
