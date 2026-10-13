<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificaEmpresa;
use App\Models\LoginPresentation;
use App\Services\LoginPresentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginPresentationTextPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Isola o provisionamento de empresa, sem remover o middleware
        // de superusuário definido no próprio controller de aparência.
        $this->withoutMiddleware(VerificaEmpresa::class);
        Cache::forget('login.presentation.active');
    }

    private function superSession(): array
    {
        return ['user_logged' => ['super' => 1, 'id' => null, 'empresa' => null]];
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Meu ambiente', 'layout' => 'split', 'font' => 'poppins',
            'hero_kicker' => 'Nosso espaço', 'headline' => 'Bem-vindo à empresa',
            'tagline' => 'Sua operação em um só lugar.', 'form_kicker' => 'Acesso restrito',
            'form_title' => 'Entre no ambiente', 'form_subtitle' => 'Use suas credenciais corporativas.',
            'accent_color' => '#1f9866', 'background_color' => '#f5f7f7',
            'rotation_seconds' => 8, 'show_hero' => 1, 'show_signup' => 1,
            'show_support' => 1, 'show_phrases' => 1, 'show_posts' => 1,
        ], $overrides);
    }

    public function test_customizing_default_creates_a_draft_and_preserves_original(): void
    {
        $service = new LoginPresentationService();
        $original = $service->defaultTemplate();
        $payload = $this->payload();
        $this->withSession($this->superSession())->post('/config/aparencia/modelos', $payload)
            ->assertSessionHasNoErrors()->assertRedirect();
        $model = LoginPresentation::query()->sole();
        $this->assertFalse($model->is_active);
        $this->assertNull($model->published_at);
        foreach (['hero_kicker', 'headline', 'tagline', 'form_kicker', 'form_title', 'form_subtitle'] as $field) {
            $this->assertSame($payload[$field], $model->settings[$field]);
        }
        $this->assertSame($original, $service->defaultTemplate());
        $this->assertNull($service->currentModel());
    }

    public function test_applying_texts_and_editing_active_version_preserves_previous_texts(): void
    {
        $this->withSession($this->superSession())
            ->post('/config/aparencia/modelos', $this->payload(['publish_now' => 1]))
            ->assertSessionHasNoErrors()->assertRedirect();
        $old = LoginPresentation::query()->sole();
        $this->assertTrue($old->is_active);
        $service = new LoginPresentationService();
        $this->assertSame('Entre no ambiente', $service->configuration()['texts']['form_title']);
        $this->post('/config/aparencia/modelos/' . $old->id, $this->payload([
            'publish_now' => 1, 'form_title' => 'Acesse o portal', 'hero_kicker' => 'Nova mensagem',
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('login_presentations', 2);
        $this->assertFalse($old->fresh()->is_active);
        $this->assertSame('Entre no ambiente', $old->fresh()->settings['form_title']);
        $this->assertSame('Acesse o portal', $service->configuration()['texts']['form_title']);
        $this->assertSame('Nova mensagem', $service->configuration()['texts']['hero_kicker']);
    }

    public function test_older_form_does_not_remove_the_new_text_settings(): void
    {
        $this->withSession($this->superSession())->post('/config/aparencia/modelos', $this->payload())
            ->assertSessionHasNoErrors();
        $model = LoginPresentation::query()->sole();
        $oldPayload = $this->payload(['name' => 'Nome atualizado']);
        foreach (['hero_kicker', 'form_kicker', 'form_title', 'form_subtitle'] as $key) {
            unset($oldPayload[$key]);
        }
        $this->post('/config/aparencia/modelos/' . $model->id, $oldPayload)->assertSessionHasNoErrors();
        $updated = $model->fresh();
        $this->assertDatabaseCount('login_presentations', 1);
        $this->assertSame('Nome atualizado', $updated->name);
        foreach (['hero_kicker', 'form_kicker', 'form_title', 'form_subtitle'] as $key) {
            $this->assertSame($this->payload()[$key], $updated->settings[$key]);
        }
    }

    public function test_empty_texts_are_persisted_without_reappearing_as_defaults(): void
    {
        $fields = ['hero_kicker', 'headline', 'tagline', 'form_kicker', 'form_title', 'form_subtitle'];
        $this->withSession($this->superSession())->post('/config/aparencia/modelos',
            $this->payload(array_fill_keys($fields, '') + ['publish_now' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertSame(array_fill_keys($fields, ''),
            (new LoginPresentationService())->configuration()['texts']);
    }

    public function test_guest_and_non_super_cannot_change_texts_and_invalid_texts_are_rejected(): void
    {
        $this->post('/config/aparencia/modelos', $this->payload())->assertRedirect('/login');

        // O Handler histórico converte HTTP 403 em redirect + flash na web.
        // Em JSON mantém o status 403. Nenhum caminho pode gravar um modelo.
        $this->withSession(['user_logged' => ['super' => 0]])
            ->post('/config/aparencia/modelos', $this->payload())
            ->assertRedirect()->assertSessionHas('mensagem_erro', 'Acesso negado.');
        $this->postJson('/config/aparencia/modelos', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('login_presentations', 0);

        $invalid = $this->payload([
            'hero_kicker' => str_repeat('x', 101), 'form_title' => ['invalido'],
        ]);
        // O Handler também apresenta ValidationException como flash na web.
        // Validamos esse retorno e os erros estruturados da resposta JSON.
        $this->withSession($this->superSession())->post('/config/aparencia/modelos', $invalid)
            ->assertRedirect()->assertSessionHas('mensagem_erro');
        $this->postJson('/config/aparencia/modelos', $invalid)
            ->assertUnprocessable()->assertJsonValidationErrors(['hero_kicker', 'form_title']);
        $this->assertDatabaseCount('login_presentations', 0);
    }
}
