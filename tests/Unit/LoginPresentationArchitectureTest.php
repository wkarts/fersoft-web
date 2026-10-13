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

        $this->expectException(\InvalidArgumentException::class);
        $service->fontFiles('https://external-fonts.invalid');
    }

    public function test_login_uses_same_authentication_recovery_and_otp_without_new_providers(): void
    {
        $base = dirname(__DIR__, 2);
        $entry = file_get_contents($base . '/resources/views/login/access_4.blade.php');
        $form = file_get_contents($base . '/resources/views/login/components/access4/form.blade.php');
        $controller = file_get_contents($base . '/app/Http/Controllers/UserController.php');
        $routes = file_get_contents($base . '/routes/web.php');

        $this->assertStringContainsString("login.components.access4.form", $entry);
        $this->assertStringContainsString("login.components.access4.otp", $entry);
        $this->assertStringNotContainsString("__view_otp_modal()", $entry);
        $this->assertStringNotContainsString('metronic/js/plugins.bundle.js', $entry);
        $otp = file_get_contents($base . '/resources/views/login/components/access4/otp.blade.php');
        $otpScript = file_get_contents($base . '/public/js/login-access4.js');
        $this->assertStringContainsString("show_otp_modal", $otp);
        $this->assertStringContainsString("url('/otp/verify-login')", $otp);
        $this->assertStringContainsString('dialog.showModal()', $otpScript);
        $this->assertStringContainsString('otp_code', $otpScript);
        $this->assertStringContainsString('loginForm.requestSubmit()', $otpScript);
        $this->assertStringContainsString("url('/login/request')", $form);
        $this->assertStringContainsString("url('/recuperarSenha')", $form);
        $this->assertStringContainsString('name="login"', $form);
        $this->assertStringContainsString('name="senha"', $form);
        $this->assertStringContainsString('name="lembrar"', $form);
        $this->assertStringContainsString('name="uri"', $form);
        $this->assertStringContainsString("config('login_presentation.page'", $controller);
        $this->assertStringNotContainsString('login_presentation.enabled', $controller);
        $this->assertStringContainsString("view()->exists('login.access_4')", $controller);
        $this->assertStringContainsString("class_exists(\\App\\Services\\LoginPresentationService::class)", $controller);
        $this->assertStringContainsString("if (\$page === 'access_4' && \$newAvailable)", $controller);
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
        $this->assertMatchesRegularExpression("/font-family:\\s*'LoginTypeface'/", $styles);
        $this->assertStringNotContainsString('font-display: swap', $login);
        $this->assertStringNotContainsString('fonts.googleapis', $login);
        $this->assertStringNotContainsString('APP_NAME=', $login);
    }

    public function test_draft_publish_and_global_super_administration_are_present(): void
    {
        $base = dirname(__DIR__, 2);
        $controller = file_get_contents($base . '/app/Http/Controllers/LoginAppearanceController.php');
        $routes = file_get_contents($base . '/routes/web.php');

        $this->assertStringContainsString("['super']", $controller);
        $this->assertStringContainsString("DB::transaction(", $controller);
        $this->assertStringContainsString("'is_active' => false", $controller);
        $this->assertStringContainsString("lockForUpdate()", $controller);
        $this->assertStringContainsString("'prefix' => 'aparencia'", $routes);
        $this->assertStringContainsString("config('login_presentation.page', 'access') === 'access_4'", $routes);
        $this->assertStringNotContainsString('login_presentation.enabled', $routes);
        $this->assertStringContainsString("class_exists(\\App\\Http\\Controllers\\LoginAppearanceController::class)", $routes);

        // A seleção de login depende apenas de PAG_LOGIN.
        $settings = file_get_contents($base . '/config/login_presentation.php');
        $this->assertStringContainsString("env('PAG_LOGIN', 'access')", $settings);
        $this->assertStringContainsString("'initial_layout' => 'split'", $settings);
        $this->assertStringContainsString("'initial_font' => 'poppins'", $settings);
        $this->assertStringNotContainsString('LOGIN_PRESENTATION_ENABLED', $settings);
        $this->assertStringNotContainsString('LOGIN_PRESENTATION_GLOBAL_BRANDING', $settings);
        $this->assertStringNotContainsString('APP_LOGIN_LAYOUT', $settings);
        $this->assertStringNotContainsString('APP_LOGIN_FONT', $settings);

        $sampleEnv = file_get_contents($base . '/.env.example');
        $this->assertStringContainsString('PAG_LOGIN=access', $sampleEnv);
        $this->assertStringNotContainsString('LOGIN_PRESENTATION_ENABLED', $sampleEnv);
        $this->assertStringNotContainsString('LOGIN_PRESENTATION_GLOBAL_BRANDING', $sampleEnv);
        $this->assertStringNotContainsString('APP_LOGIN_LAYOUT', $sampleEnv);
        $this->assertStringNotContainsString('APP_LOGIN_FONT', $sampleEnv);
    }

    public function test_each_login_presentation_table_has_its_own_standard_migration(): void
    {
        $directory = dirname(__DIR__, 2) . '/database/migrations/';
        $expected = [
            '2026_10_12_180500_create_login_presentations_table.php' => 'login_presentations',
            '2026_10_12_180501_create_login_presentation_contents_table.php' => 'login_presentation_contents',
            '2026_10_12_180502_create_login_presentation_assets_table.php' => 'login_presentation_assets',
        ];

        $this->assertFileDoesNotExist(
            $directory . '2026_10_12_180500_create_login_presentation_tables.php'
        );

        foreach ($expected as $filename => $table) {
            $file = $directory . $filename;
            $this->assertFileExists($file);
            $migration = file_get_contents($file);

            // Cada arquivo cria e desfaz somente sua própria tabela.
            $this->assertStringContainsString('public function up(): void', $migration);
            $this->assertStringContainsString('public function down(): void', $migration);
            $this->assertStringContainsString("Schema::create('{$table}'", $migration);
            $this->assertStringContainsString("Schema::dropIfExists('{$table}')", $migration);
            $this->assertSame(1, substr_count($migration, 'Schema::create('));
            $this->assertSame(1, substr_count($migration, 'Schema::dropIfExists('));
            $this->assertStringContainsString("Schema::hasTable('{$table}')", $migration);

            // Um rollback nunca deve apagar dados de uma instalação já
            // provisionada pela migration agrupada anterior.
            $this->assertStringContainsString(
                "'2026_10_12_180500_create_login_presentation_tables'",
                $migration
            );
            $this->assertStringContainsString("->nullable()", $migration);
        }
    }
}
