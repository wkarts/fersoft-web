<?php

namespace Tests\Feature;

use Tests\TestCase;

class OptionalLoginPresentationCoexistenceTest extends TestCase
{
    private ?string $originalRequestUri = null;
    private bool $requestUriWasSet = false;

    protected function setUp(): void
    {
        parent::setUp();

        // O view composer histórico lê REQUEST_URI diretamente.
        $this->requestUriWasSet = array_key_exists('REQUEST_URI', $_SERVER);
        $this->originalRequestUri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/login';
    }

    protected function tearDown(): void
    {
        if ($this->requestUriWasSet) {
            $_SERVER['REQUEST_URI'] = $this->originalRequestUri;
        } else {
            unset($_SERVER['REQUEST_URI']);
        }

        parent::tearDown();
    }

    public function test_historical_access_remains_available_by_page_selection(): void
    {
        config()->set('login_presentation.page', 'access');

        $this->get('/login')->assertOk()
            ->assertSee('kt_login_signin_form', false)
            ->assertDontSee('id="login-access4"', false);
    }

    public function test_historical_access_2_remains_available_by_page_selection(): void
    {
        config()->set('login_presentation.page', 'access_2');

        $this->get('/login')->assertOk()
            ->assertSee('kt_login_signin_form', false)
            ->assertDontSee('id="login-access4"', false);
    }

    public function test_historical_access_3_remains_available_by_page_selection(): void
    {
        config()->set('login_presentation.page', 'access_3');

        $this->get('/login')->assertOk()
            ->assertSee('kt_login_signin_form', false)
            ->assertDontSee('id="login-access4"', false);
    }

    public function test_unknown_page_falls_back_to_installed_historical_login(): void
    {
        config()->set('login_presentation.page', 'missing_login_variant');

        $this->get('/login')->assertOk()
            ->assertSee('kt_login_signin_form', false)
            ->assertDontSee('id="login-access4"', false);
    }

    public function test_access4_is_activated_only_by_page_selection_without_new_env_flags(): void
    {
        config()->set('login_presentation.page', 'access_4');

        $this->get('/login')->assertOk()
            ->assertSee('id="login-access4"', false)
            ->assertSee('name="login"', false)
            ->assertSee('name="senha"', false)
            ->assertSee('/login/request', false);
    }

    public function test_pwa_manifest_remains_available_with_historical_login_selected(): void
    {
        config()->set('login_presentation.page', 'access');

        $this->get('/app-install/manifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8');
    }

    public function test_pwa_manifest_remains_available_with_access4_selected(): void
    {
        config()->set('login_presentation.page', 'access_4');

        $this->get('/app-install/manifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8');
    }

    public function test_access4_otp_is_hidden_by_default_and_uses_native_dialog(): void
    {
        config()->set('login_presentation.page', 'access_4');

        $this->get('/login')->assertOk()
            ->assertSee('<dialog id="otpModal"', false)
            ->assertSee('data-show-otp="false"', false)
            ->assertSee('js/login-access4.js', false)
            ->assertDontSee('metronic/js/plugins.bundle.js', false);
    }

    public function test_access4_otp_dialog_is_requested_after_first_authentication_step(): void
    {
        config()->set('login_presentation.page', 'access_4');

        $this->withSession(['show_otp_modal' => true])
            ->get('/login')
            ->assertOk()
            ->assertSee('data-show-otp="true"', false)
            ->assertSee('/otp/verify-login', false);
    }

    public function test_invalid_credentials_redirect_instead_of_accessing_null_user_id(): void
    {
        config()->set('login_presentation.page', 'access_4');

        $this->post('/login/request', [
            'login' => 'test-usuario-inexistente-access4-20261012',
            'senha' => 'invalida-access4-regressao',
        ])->assertRedirect('/login')
          ->assertSessionHas('mensagem_login', 'Credencial(s) incorreta(s)!');
    }
}
