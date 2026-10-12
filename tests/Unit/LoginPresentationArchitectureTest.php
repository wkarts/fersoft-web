<?php

namespace Tests\Unit;

use App\Services\LoginPresentationService;
use PHPUnit\Framework\TestCase;

class LoginPresentationArchitectureTest extends TestCase
{
    public function test_eight_layout_presets_reuse_one_blade_page(): void
    {
        $service = new LoginPresentationService();
        $presets = $service->presets();

        $this->assertCount(8, $presets);
        foreach (['split','centered','corporate','editorial','minimal','institutional','highlights','wide'] as $layout) {
            $this->assertTrue($service->validLayout($layout));
        }

        $base = dirname(__DIR__, 2);
        $this->assertFileExists($base . '/resources/views/login/access_4.blade.php');
        $this->assertFileExists($base . '/public/css/login-access4.css');
        $this->assertFalse($service->validLayout('../../config'));
    }

    public function test_fonts_reference_real_local_files_without_external_providers(): void
    {
        $service = new LoginPresentationService();
        $base = dirname(__DIR__, 2);

        foreach ($service->fonts() as $key => $label) {
            $this->assertTrue($service->validFont($key));
            foreach ([400,600,700] as $weight) {
                $path = $service->fontFiles($key)[$weight];
                $this->assertFileExists($base . '/public/' . $path);
                $this->assertStringNotContainsString('http', $path);
            }
        }

        $this->assertFalse($service->validFont('https://external-fonts.invalid'));
    }

    public function test_login_uses_same_authentication_recovery_and_otp_without_new_providers(): void
    {
        $base = dirname(__DIR__, 2);
        $entry = file_get_contents($base . '/resources/views/login/access_4.blade.php');
        $form = file_get_contents($base . '/resources/views/login/components/access4/form.blade.php');
        $controller = file_get_contents($base . '/app/Http/Controllers/UserController.php');
        $routes = file_get_contents($base . '/routes/web.php');

        $this->assertStringContainsString("login.components.access4.form", $entry);
        $this->assertStringContainsString("__view_otp_modal()", $entry);
        $this->assertStringContainsString("show_otp_modal", $entry);
        $this->assertStringContainsString("url('/login/request')", $form);
        $this->assertStringContainsString("url('/recuperarSenha')", $form);
        $this->assertStringContainsString('name="login"', $form);
        $this->assertStringContainsString('name="senha"', $form);
        $this->assertStringContainsString('name="lembrar"', $form);
        $this->assertStringContainsString('name="uri"', $form);
        $this->assertStringContainsString("if (\$loginPage === 'access_4')", $controller);
        $this->assertStringContainsString("Route::post('/request', 'UserController@request')->middleware('acessoUsuario')", $routes);
    }

    public function test_public_appearance_has_one_source_of_name_and_one_effective_typography(): void
    {
        $base = dirname(__DIR__, 2);
        $login = file_get_contents($base . '/resources/views/login/access_4.blade.php');
        $identity = file_get_contents($base . '/resources/views/login/components/access4/identity.blade.php');
        $service = file_get_contents($base . '/app/Services/LoginPresentationService.php');
        $styles = file_get_contents($base . '/public/css/login-access4.css');

        $this->assertStringContainsString("config('app.name')", $login);
        $this->assertStringContainsString("config('app.name')", $identity);
        $this->assertStringContainsString("config('app.name')", $service);
        $this->assertStringContainsString('font-display: block;', $login);
        $this->assertStringContainsString("font-family:'LoginTypeface'", $styles);
        $this->assertStringNotContainsString('font-display: swap', $login);
        $this->assertStringNotContainsString('fonts.googleapis', $login);
        $this->assertStringNotContainsString('APP_NAME=', $login);
    }

    public function test_draft_publish_and_global_super_administration_are_present(): void
    {
        $base = dirname(__DIR__, 2);
        $controller = file_get_contents($base . '/app/Http/Controllers/LoginAppearanceController.php');
        $routes = file_get_contents($base . '/routes/web.php');
        $migration = file_get_contents($base . '/database/migrations/2026_10_12_180500_create_login_presentation_tables.php');

        $this->assertStringContainsString("['super']", $controller);
        $this->assertStringContainsString("DB::transaction(", $controller);
        $this->assertStringContainsString("'is_active' => false", $controller);
        $this->assertStringContainsString("lockForUpdate()", $controller);
        $this->assertStringContainsString("'prefix' => 'aparencia'", $routes);
        $this->assertStringNotContainsString('Schema::drop', $migration);
        $this->assertStringContainsString("->nullable()", $migration);
    }
}
