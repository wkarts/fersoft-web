<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificaEmpresa;
use App\Models\LoginPresentation;
use App\Services\LoginLogoSettings;
use App\Services\LoginPresentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginLogoPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerificaEmpresa::class);
        Cache::forget('login.presentation.active');
    }

    private function payload(array $extra = []): array
    {
        return array_replace([
            'name' => 'Logo personalizada', 'layout' => 'split', 'font' => 'poppins',
            'headline' => 'Meu ambiente', 'tagline' => 'Descrição preservada',
            'hero_kicker' => 'Minha apresentação', 'form_title' => 'Meu acesso',
            'accent_color' => '#1f9866', 'background_color' => '#f5f7f7',
            'rotation_seconds' => 8, 'show_hero' => 1, 'show_signup' => 1,
            'show_support' => 1, 'show_phrases' => 1, 'show_posts' => 1,
        ], $extra);
    }

    private function superSession(): array
    {
        return ['user_logged' => ['super' => 1, 'id' => null, 'empresa' => null]];
    }

    public function test_logo_is_saved_in_existing_settings_and_active_versions_are_not_overwritten(): void
    {
        $service = app(LoginPresentationService::class);
        $original = $service->defaultTemplate();
        $this->withSession($this->superSession())->post('/config/aparencia/modelos', $this->payload([
            'publish_now' => 1,
            'logo' => ['desktop' => ['width' => 220, 'align' => 'right'], 'mobile' => ['height' => 64, 'align' => 'center']],
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $published = LoginPresentation::query()->sole();
        $this->assertTrue($published->is_active);
        $this->assertSame(220, $published->settings['logo']['desktop']['width']);
        $this->assertSame(64, $published->settings['logo']['mobile']['height']);
        $this->assertSame('Minha apresentação', $published->settings['hero_kicker']);
        $this->assertSame('220', $service->configuration()['logo']['attributes']['data-logo-desktop-width']);

        $this->post('/config/aparencia/modelos/' . $published->id, $this->payload([
            'publish_now' => 1, 'logo' => ['desktop' => ['width' => 300]],
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('login_presentations', 2);
        $this->assertSame(220, $published->fresh()->settings['logo']['desktop']['width']);
        $this->assertFalse($published->fresh()->is_active);
        $current = $service->currentModel();
        $this->assertSame(300, $current->settings['logo']['desktop']['width']);
        $this->assertSame(64, $current->settings['logo']['mobile']['height']);
        $this->assertSame($original, $service->defaultTemplate());
    }

    public function test_older_forms_keep_logo_values_and_reset_only_affects_the_selected_scope(): void
    {
        $this->withSession($this->superSession())->post('/config/aparencia/modelos', $this->payload([
            'logo' => ['desktop' => ['width' => 210], 'mobile' => ['width' => 96]],
        ]))->assertSessionHasNoErrors();
        $model = LoginPresentation::query()->sole();
        $this->post('/config/aparencia/modelos/' . $model->id, $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(210, $model->fresh()->settings['logo']['desktop']['width']);
        $this->assertSame(96, $model->fresh()->settings['logo']['mobile']['width']);
        $this->post('/config/aparencia/modelos/' . $model->id, $this->payload([
            'logo' => ['desktop' => LoginLogoSettings::defaults()],
        ]))->assertSessionHasNoErrors();
        // Objetos JSON não têm ordem de chaves. Verifica a estrutura e cada
        // valor/tipo estritamente, sem depender da ordenação interna do MySQL.
        $restored = $model->fresh()->settings['logo']['desktop'];
        $this->assertCount(count(LoginLogoSettings::defaults()), $restored);
        foreach (LoginLogoSettings::defaults() as $field => $expected) {
            $this->assertArrayHasKey($field, $restored);
            $this->assertSame($expected, $restored[$field]);
        }
        $this->assertSame(96, $model->fresh()->settings['logo']['mobile']['width']);
        $this->post('/config/aparencia/modelos/' . $model->id . '/duplicar')->assertRedirect();
        $copy = LoginPresentation::query()->orderByDesc('id')->first();
        $this->assertSame($model->fresh()->settings, $copy->settings);
        $this->assertFalse($copy->is_active);
    }

    public function test_invalid_sizes_and_css_values_are_rejected_by_existing_controller(): void
    {
        $this->withSession($this->superSession())->postJson('/config/aparencia/modelos', $this->payload([
            'logo' => ['desktop' => ['width' => '240px', 'align' => 'fixed'], 'mobile' => ['height' => 181, 'inset' => -1]],
        ]))->assertUnprocessable()->assertJsonValidationErrors([
            'logo.desktop.width', 'logo.desktop.align', 'logo.mobile.height', 'logo.mobile.inset',
        ]);
        $this->assertDatabaseCount('login_presentations', 0);
    }

    public function test_non_super_cannot_save_logo_settings(): void
    {
        $this->withSession(['user_logged' => ['super' => 0]])
            ->postJson('/config/aparencia/modelos', $this->payload(['logo' => ['desktop' => ['width' => 180]]]))
            ->assertForbidden();
        $this->assertDatabaseCount('login_presentations', 0);
    }

    public function test_logo_editor_partial_renders_both_profiles_and_reset_controls(): void
    {
        $hadUri = array_key_exists('REQUEST_URI', $_SERVER);
        $uri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/login';
        try {
            $html = view('config.login_appearance.partials.logo', [
                'editorSettings' => ['logo' => ['desktop' => ['width' => 220], 'mobile' => ['width' => 90]]],
            ])->render();
            $this->assertStringContainsString('name="logo[desktop][width]"', $html);
            $this->assertStringContainsString('name="logo[mobile][width]"', $html);
            $this->assertStringContainsString('value="220"', $html);
            $this->assertStringContainsString('value="90"', $html);
            $this->assertStringContainsString('data-logo-reset="desktop"', $html);
            $this->assertStringContainsString('data-logo-reset="mobile"', $html);
        } finally {
            if ($hadUri) $_SERVER['REQUEST_URI'] = $uri;
            else unset($_SERVER['REQUEST_URI']);
        }
    }
}
