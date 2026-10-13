<?php

namespace Tests\Unit;

use App\Services\LoginPresentationService;
use Tests\TestCase;

class LoginAppearanceWorkspaceTest extends TestCase
{
    public function test_eight_complete_templates_are_always_available_without_database_seeding(): void
    {
        $service = app(LoginPresentationService::class);
        $templates = $service->readyTemplates();

        $this->assertCount(8, $templates);
        $this->assertSame(array_keys($service->presets()), array_keys($templates));

        foreach ($templates as $key => $template) {
            $this->assertSame($key, $template['layout']);
            $this->assertTrue($service->validLayout($template['layout']));
            $this->assertTrue($service->validFont($template['font']));
            $this->assertNotEmpty($template['name']);
            $this->assertNotEmpty($template['description']);
            $this->assertIsArray($template['settings']);
            $this->assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $template['settings']['accent_color']);
            $this->assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $template['settings']['background_color']);

            foreach ([
                'headline', 'tagline', 'rotation_seconds', 'show_hero',
                'show_posts', 'show_phrases', 'show_signup', 'show_support',
            ] as $field) {
                $this->assertArrayHasKey($field, $template['settings']);
            }
        }

        $this->assertNotSame(
            $templates['editorial']['settings']['accent_color'],
            $templates['corporate']['settings']['accent_color']
        );
    }

    public function test_original_access4_definition_is_immutable_even_after_modifying_catalog_values(): void
    {
        $service = app(LoginPresentationService::class);
        $original = $service->defaultTemplate();
        $templates = $service->readyTemplates();

        $this->assertSame('split', $original['layout']);
        $this->assertSame('poppins', $original['font']);
        $this->assertSame('#1f9866', $original['settings']['accent_color']);
        $this->assertTrue($original['settings']['show_hero']);

        $templates['split']['settings']['accent_color'] = '#ffffff';
        $templates['split']['settings']['show_hero'] = false;

        $this->assertSame($original, $service->defaultTemplate());
        $this->assertSame('#16885d', $service->readyTemplates()['split']['settings']['accent_color']);
    }

    public function test_login_is_full_width_and_screen_height_without_external_card_or_duplicate_app_name(): void
    {
        $css = file_get_contents(public_path('css/login-access4.css'));

        $this->assertStringContainsString('body.login-access4 {', $css);
        $this->assertStringContainsString('box-sizing: border-box;', $css);
        $this->assertStringContainsString('height: 100dvh;', $css);
        $this->assertStringContainsString('width: 100%;', $css);
        $this->assertStringContainsString('border-radius: 0;', $css);
        $this->assertStringContainsString('box-shadow: none;', $css);
        $this->assertStringContainsString('.login-access4 .sr-only', $css);
        $this->assertStringContainsString('height: auto;', $css);
        $this->assertStringNotContainsString('box-shadow: 0 24px 65px', $css);
    }

    public function test_editor_is_centralized_and_offers_instant_publish_with_automatic_version_creation(): void
    {
        $page = file_get_contents(resource_path('views/config/login_appearance/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/LoginAppearanceController.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        foreach (['modelos', 'editor', 'conteudos', 'identidade'] as $section) {
            $this->assertStringContainsString('data-appearance-panel="' . $section . '"', $page);
        }
        $this->assertStringContainsString("foreach(\$readyTemplates as \$key => \$template)", $page);
        $this->assertStringContainsString('Salvar e aplicar no login', $page);
        $this->assertStringContainsString('name="publish_now"', $page);
        $this->assertStringContainsString('appearance-preview-frame', $page);
        $this->assertStringContainsString("config.login_appearance.partials.contents", $page);
        $this->assertStringContainsString("config.login_appearance.partials.assets", $page);

        $this->assertStringContainsString('function applyReadyTemplate(', $controller);
        $this->assertStringContainsString('function restoreDefault()', $controller);
        $this->assertStringContainsString('function previewDefault()', $controller);
        $this->assertStringContainsString('function previewReadyTemplate(', $controller);
        $this->assertStringContainsString("\$request->boolean('publish_now')", $controller);
        $this->assertStringContainsString("if (\$model->is_active)", $controller);
        $this->assertStringContainsString("'published_at' => null", $controller);
        $this->assertStringNotContainsString('->delete()', $controller);
        $this->assertStringNotContainsString('->truncate()', $controller);

        $this->assertStringContainsString("Route::get('/preview/padrao'", $routes);
        $this->assertStringContainsString("Route::get('/preview/predefinido/{layout}'", $routes);
        $this->assertStringContainsString("Route::post('/modelos/predefinido/{layout}'", $routes);
        $this->assertStringContainsString("Route::post('/padrao/restaurar'", $routes);
    }

    public function test_existing_content_and_branding_operations_remain_available_in_new_sections(): void
    {
        $contents = file_get_contents(resource_path('views/config/login_appearance/partials/contents.blade.php'));
        $assets = file_get_contents(resource_path('views/config/login_appearance/partials/assets.blade.php'));
        $javascript = file_get_contents(public_path('js/login-appearance-admin.js'));
        $css = file_get_contents(public_path('css/login-appearance-admin.css'));

        foreach ([
            "url('/config/aparencia/conteudos')",
            "url('/config/aparencia/conteudos/' . \$content->id)",
            "'/arquivar'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $contents);
        }

        $this->assertStringContainsString("url('/config/aparencia/arquivos/' . \$key)", $assets);
        $this->assertStringContainsString("'login_logo'=>'Logo da tela de acesso'", $assets);
        $this->assertStringContainsString("'menu_logo'=>'Logo institucional do menu'", $assets);
        $this->assertStringContainsString("'favicon'=>'Favicon e ícone da instalação'", $assets);

        $this->assertStringContainsString("data-appearance-tab", $javascript);
        $this->assertStringContainsString("frame.addEventListener('load'", $javascript);
        $this->assertStringContainsString("data-appearance-editor", file_get_contents(resource_path('views/config/login_appearance/index.blade.php')));
        $this->assertStringContainsString('@media(max-width:490px)', $css);
    }
}
