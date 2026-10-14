<?php

namespace Tests\Feature;

use Tests\TestCase;

class UiThemeIsolationTest extends TestCase
{
    public function test_seletor_de_tema_so_habilita_o_refinado_explicitamente(): void
    {
        $source = file_get_contents(config_path('ui_theme.php'));

        $this->assertStringContainsString("env('APP_UI_THEME', 'classic')", $source);
        $this->assertStringContainsString("$" . "mode === 'refined'", $source);
        $this->assertStringContainsString("'active' => $" . "active", $source);
    }

    public function test_ambos_layouts_autenticados_possuem_chave_seletiva(): void
    {
        foreach (['menu_lateral', 'menu_superior'] as $layout) {
            $source = file_get_contents(resource_path("views/default/{$layout}.blade.php"));

            $this->assertStringContainsString("config('ui_theme.active', false)", $source);
            $this->assertStringContainsString("ui-theme-refined", $source);
            $this->assertStringContainsString("themes.refined.assets", $source);
        }
    }

    public function test_css_do_tema_nao_usa_seletores_globais_para_substituir_o_legado(): void
    {
        $css = file_get_contents(public_path('css/theme-refined.css'));

        $this->assertStringContainsString('body.ui-theme-refined', $css);
        $this->assertStringContainsString('body.ui-theme-refined--dark', $css);
        $this->assertStringNotContainsString(':root {', $css);
        $this->assertStringNotContainsString('body {', $css);
        $this->assertStringNotContainsString('z-index:', $css);
    }

    public function test_fontes_reutilizam_o_repositorio_do_access4(): void
    {
        $assets = file_get_contents(resource_path('views/themes/refined/assets.blade.php'));

        $this->assertStringContainsString('login2/fonts/poppins/Poppins-Regular.ttf', $assets);
        $this->assertStringContainsString('LoginPresentationService', $assets);
        $this->assertStringContainsString('fontFiles', $assets);
    }
}
