<?php

namespace Tests\Feature;

use Tests\TestCase;

class UiThemeIsolationTest extends TestCase
{
    public function test_tema_aprovado_e_padrao_e_classic_e_rollback_explicito(): void
    {
        $source = file_get_contents(config_path('ui_theme.php'));

        $this->assertStringContainsString("env('APP_UI_THEME', 'refined')", $source);
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

    public function test_css_proprio_fica_isolado_no_shell_aprovado(): void
    {
        $css = file_get_contents(public_path('css/fer-design-system.css'));

        $this->assertStringContainsString('body.ui-native-shell', $css);
        $this->assertStringContainsString('body.ui-native-shell.ui-theme-refined--dark', $css);
        $this->assertStringNotContainsString(':root {', $css);
        $this->assertDoesNotMatchRegularExpression('/^[ \t]*body[ \t]*\{/m', $css);
        $this->assertStringNotContainsString('/metronic/', $css);
    }

    public function test_inventario_php_executa_sem_alterar_as_views(): void
    {
        $process = new \Symfony\Component\Process\Process([
            PHP_BINARY,
            base_path('scripts/auditar-tema.php'),
        ], base_path());
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            $process->getErrorOutput() . PHP_EOL . $process->getOutput()
        );
        $this->assertStringContainsString('Views Blade:', $process->getOutput());
        $this->assertStringContainsString('Módulos mais volumosos:', $process->getOutput());
    }

    public function test_fontes_reutilizam_o_repositorio_do_access4(): void
    {
        $assets = file_get_contents(resource_path('views/themes/refined/assets.blade.php'));

        $this->assertStringContainsString('login2/fonts/poppins/Poppins-Regular.ttf', $assets);
        $this->assertStringContainsString('LoginPresentationService', $assets);
        $this->assertStringContainsString('fontFiles', $assets);
    }
}
