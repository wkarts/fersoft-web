<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LoginAccess4VisualRegressionTest extends TestCase
{
    public function test_modal_uses_own_accessible_dialog_without_external_bootstrap_dependencies(): void
    {
        $base = dirname(__DIR__, 2);
        $page = file_get_contents($base . '/resources/views/login/access_4.blade.php');
        $otp = file_get_contents($base . '/resources/views/login/components/access4/otp.blade.php');
        $javascript = file_get_contents($base . '/public/js/login-access4.js');
        $css = file_get_contents($base . '/public/css/login-access4.css');

        $this->assertStringContainsString("login.components.access4.otp", $page);
        $this->assertStringNotContainsString('__view_otp_modal()', $page);
        $this->assertStringNotContainsString('metronic/js/plugins.bundle.js', $page);
        $this->assertStringContainsString('<dialog id="otpModal"', $otp);
        $this->assertStringContainsString('aria-labelledby="otpModalLabel"', $otp);
        $this->assertStringContainsString('aria-describedby="otpModalDescription"', $otp);
        $this->assertStringContainsString('data-show-otp="{{ session(', $otp);
        $this->assertStringContainsString("url('/otp/verify-login')", $otp);
        $this->assertStringContainsString('dialog.showModal()', $javascript);
        $this->assertStringContainsString("credentials: 'same-origin'", $javascript);
        $this->assertStringContainsString("requestBody.set('otp_code', code)", $javascript);
        $this->assertStringContainsString('loginForm.requestSubmit()', $javascript);
        $this->assertStringContainsString('.login-otp-dialog::backdrop', $css);
        $this->assertStringContainsString('.login-access4 [hidden]', $css);
    }

    public function test_each_composition_and_mobile_viewport_is_supported_without_repeating_default_hero_logo(): void
    {
        $base = dirname(__DIR__, 2);
        $styles = file_get_contents($base . '/public/css/login-access4.css');
        $hero = file_get_contents($base . '/resources/views/login/components/access4/hero.blade.php');
        $service = file_get_contents($base . '/app/Services/LoginPresentationService.php');

        foreach (['corporate', 'editorial', 'institutional', 'highlights', 'wide', 'centered', 'minimal'] as $name) {
            $this->assertStringContainsString('.login-layout-' . $name, $styles);
        }
        $this->assertStringContainsString('grid-template-columns:', $styles);
        $this->assertStringContainsString('@media (max-width: 960px)', $styles);
        $this->assertStringContainsString('@media (max-width: 520px)', $styles);
        $this->assertStringContainsString('hero_is_custom', $hero);
        $this->assertStringContainsString("'hero_is_custom' =>", $service);
        $this->assertStringContainsString('login-form-mobile-brand', $styles);
    }

    public function test_super_menu_has_direct_link_to_existing_appearance_editor_in_both_variants(): void
    {
        $base = dirname(__DIR__, 2);
        foreach (['menu_lateral.blade.php', 'menu_superior.blade.php'] as $name) {
            $view = file_get_contents($base . '/resources/views/default/' . $name);

            $this->assertStringContainsString("session('user_logged')['super'] == 1", $view);
            $this->assertStringContainsString("url('/config/aparencia')", $view);
            $this->assertStringContainsString('Aparência do Login', $view);
        }
    }

    public function test_missing_user_is_rejected_before_dereferencing_user_id(): void
    {
        $base = dirname(__DIR__, 2);
        $controller = file_get_contents($base . '/app/Http/Controllers/UserController.php');
        $guard = strpos($controller, 'if ($usr === null)');
        $dereference = strpos($controller, "session(['usr_otp_id' => \$usr->id])");

        $this->assertNotFalse($guard);
        $this->assertNotFalse($dereference);
        $this->assertLessThan($dereference, $guard);
        $this->assertStringContainsString("is_file(public_path('js/login-access4.js'))", $controller);
    }
}
