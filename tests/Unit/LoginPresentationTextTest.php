<?php

namespace Tests\Unit;

use App\Services\LoginPresentationService;
use Tests\TestCase;

class LoginPresentationTextTest extends TestCase
{
    public function test_existing_factory_and_presets_keep_the_same_visible_texts(): void
    {
        $service = new LoginPresentationService();
        config()->set('login_presentation.initial_tagline', '');
        $original = $service->defaultTemplate();
        $text = $service->presentationTexts($original['settings']);
        $this->assertSame('Seu ambiente', $text['hero_kicker']);
        $this->assertSame('Tudo pronto para continuar.', $text['headline']);
        $this->assertSame('Acesse seu ambiente de trabalho com segurança e praticidade.', $text['tagline']);
        $this->assertSame('Área de acesso', $text['form_kicker']);
        $this->assertSame('Acesse sua conta', $text['form_title']);
        $this->assertSame('Informe suas credenciais para continuar.', $text['form_subtitle']);
        foreach ($service->readyTemplates() as $preset) {
            $settings = $preset['settings'];
            $copy = $service->presentationTexts($settings);
            if ($settings['headline'] !== '' || $settings['tagline'] !== '') {
                $this->assertSame('Seu espaço de trabalho', $copy['hero_kicker']);
                $this->assertSame($settings['headline'], $copy['headline']);
                $this->assertSame($settings['tagline'], $copy['tagline']);
            }
            $this->assertSame('Acesse sua conta', $copy['form_title']);
        }
        $this->assertSame($original, $service->defaultTemplate());
    }

    public function test_all_six_texts_are_independent_and_can_be_explicitly_hidden(): void
    {
        $service = new LoginPresentationService();
        $settings = [
            'hero_kicker' => 'Nosso ambiente', 'headline' => 'Bem-vindo à empresa',
            'tagline' => 'Sua operação simplificada.', 'form_kicker' => 'Acesso restrito',
            'form_title' => 'Entre no sistema', 'form_subtitle' => 'Use suas credenciais.',
        ];
        $this->assertSame($settings, $service->presentationTexts($settings));
        $this->assertSame(array_fill_keys(array_keys($settings), ''),
            $service->presentationTexts(array_fill_keys(array_keys($settings), '')));
        $this->assertSame(array_fill_keys(array_keys($settings), ''),
            $service->presentationTexts(array_fill_keys(array_keys($settings), null)));
    }

    public function test_missing_optional_values_keep_legacy_fallback_and_partial_hero_text(): void
    {
        $service = new LoginPresentationService();
        $copy = $service->presentationTexts(['headline' => '', 'tagline' => 'Descrição histórica']);
        $this->assertSame('', $copy['headline']);
        $this->assertSame('Descrição histórica', $copy['tagline']);
        $this->assertSame('Seu espaço de trabalho', $copy['hero_kicker']);
        $copy = $service->presentationTexts(['form_title' => 'Título personalizado']);
        $this->assertSame('Título personalizado', $copy['form_title']);
        $this->assertSame('Tudo pronto para continuar.', $copy['headline']);
        $this->assertSame('Seu ambiente', $copy['hero_kicker']);
    }

    public function test_editor_and_public_blades_use_escaped_text_and_same_preview_bindings(): void
    {
        $editor = file_get_contents(resource_path('views/config/login_appearance/partials/texts.blade.php'));
        $hero = file_get_contents(resource_path('views/login/components/access4/hero.blade.php'));
        $form = file_get_contents(resource_path('views/login/components/access4/form.blade.php'));
        $script = file_get_contents(public_path('js/login-appearance-admin.js'));
        foreach (['hero_kicker', 'headline', 'tagline', 'form_kicker', 'form_title', 'form_subtitle'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $editor);
            $this->assertStringContainsString('data-login-text="' . $field . '"', $hero . $form);
            $this->assertStringContainsString("{{ \$presentation['texts']['{$field}'] }}", $hero . $form);
            $this->assertStringContainsString("'{$field}'", $script);
        }
        $this->assertStringContainsString('element.textContent = text', $script);
        $this->assertStringContainsString("element.hidden = text === ''", $script);
        $this->assertStringNotContainsString('element.innerHTML', $script);
        $this->assertStringContainsString("url('/login/request')", $form);
        $this->assertStringContainsString("url('/recuperarSenha')", $form);
    }
}
