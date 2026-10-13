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

        // O view composer histórico lê o superglobal diretamente. Em HTTP
        // real ele existe, mas o cliente de testes não o preenche.
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

    public function test_existing_access_is_unchanged_when_optional_module_is_disabled(): void
    {
        config()->set('login_presentation.enabled', false);
        config()->set('login_presentation.global_branding_enabled', false);
        config()->set('login_presentation.page', 'access');

        $this->get('/login')->assertOk()
            ->assertSee('kt_login_signin_form', false)
            ->assertDontSee('id="login-access4"', false);
    }

    public function test_requesting_new_page_while_disabled_stays_on_original_login(): void
    {
        config()->set('login_presentation.enabled', false);
        config()->set('login_presentation.global_branding_enabled', false);
        config()->set('login_presentation.page', 'access_4');

        $this->get('/login')->assertOk()
            ->assertSee('kt_login_signin_form', false)
            ->assertDontSee('id="login-access4"', false);
    }

    public function test_historical_alternative_works_even_when_new_module_is_enabled(): void
    {
        config()->set('login_presentation.enabled', true);
        config()->set('login_presentation.global_branding_enabled', false);
        config()->set('login_presentation.page', 'access_2');

        $this->get('/login')->assertOk()
            ->assertSee('kt_login_signin_form', false)
            ->assertDontSee('id="login-access4"', false);
    }

    public function test_new_page_can_run_without_requiring_a_published_template(): void
    {
        config()->set('login_presentation.enabled', true);
        config()->set('login_presentation.global_branding_enabled', false);
        config()->set('login_presentation.page', 'access_4');

        $this->get('/login')->assertOk()
            ->assertSee('id="login-access4"', false)
            ->assertSee('name="login"', false)
            ->assertSee('name="senha"', false)
            ->assertSee('/login/request', false);
    }

    public function test_existing_pwa_resources_are_independent_of_new_module(): void
    {
        config()->set('login_presentation.enabled', false);
        config()->set('login_presentation.global_branding_enabled', false);

        $this->get('/app-install/manifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8');
    }
}
