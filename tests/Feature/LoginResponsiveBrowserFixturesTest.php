<?php

namespace Tests\Feature;

use App\Models\LoginPresentation;
use App\Services\LoginPresentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LoginResponsiveBrowserFixturesTest extends TestCase
{
    use RefreshDatabase;

    /** Gera HTML das Blades reais para Playwright, apenas no banco isolado de teste. */
    public function test_export_actual_blade_pages_for_browser_regressions(): void
    {
        $hadUri = array_key_exists('REQUEST_URI', $_SERVER);
        $previousUri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/login';
        $directory = storage_path('framework/testing/access4-fixtures');
        File::ensureDirectoryExists($directory);
        config()->set('app.url', 'http://127.0.0.1:8765');
        config()->set('app.email_suporte', 'suporte@example.test');
        URL::forceRootUrl('http://127.0.0.1:8765');
        session()->forget(['user_logged', 'usr_otp']);
        session()->put('mensagem_login', 'Logoff realizado!');
        view()->share('errors', new ViewErrorBag());
        $service = app(LoginPresentationService::class);
        $manifest = [];

        try {
            $templates = ['default' => $service->defaultTemplate()] + $service->readyTemplates();
            foreach ($templates as $templateKey => $template) {
                $layout = $template['layout'];
                foreach (['poppins', 'montserrat'] as $font) {
                    foreach (['empty', 'content'] as $scenario) {
                        $model = new LoginPresentation([
                            'name' => $template['name'],
                            'layout' => $layout,
                            'font' => $font,
                            'settings' => $template['settings'],
                        ]);
                        $presentation = $service->configuration($model);
                        // O pior caso preserva cadastro, suporte e conteúdo em todas as composições.
                        $presentation['show_signup'] = true;
                        $presentation['show_support'] = true;
                        $presentation['show_posts'] = true;
                        $presentation['show_phrases'] = true;
                        $presentation['rotation_seconds'] = 4;
                        $presentation['contents'] = ['phrases' => [], 'posts' => [], 'notices' => []];
                        if ($scenario === 'content') {
                            $longWord = str_repeat('TextoSemEspacos', 24);
                            $presentation['headline'] = $longWord;
                            $presentation['tagline'] = 'Descrição longa: ' . $longWord;
                            $presentation['contents'] = [
                                'phrases' => [
                                    ['body' => 'Primeira frase de teste'],
                                    ['body' => 'Segunda frase de teste'],
                                ],
                                'notices' => [['title' => 'Aviso publicado', 'body' => $longWord]],
                                'posts' => [['title' => 'Notícia publicada', 'body' => $longWord,
                                    'image' => asset('imgs/login2.png'), 'url' => 'https://example.test/noticia']],
                            ];
                        }
                        $html = view('login.access_4', [
                            'presentation' => $presentation,
                            'sessaoAtiva' => false,
                            'previewMode' => false,
                            'loginCookie' => 'browser-fixture-user',
                            'senhaCookie' => 'browser-fixture-password',
                            'lembrarCookie' => '',
                        ])->render();
                        $name = $templateKey . '-' . $font . '-' . $scenario . '.html';
                        File::put($directory . '/' . $name, $html);
                        $manifest[] = compact('name', 'layout', 'font', 'scenario', 'templateKey');
                        $this->assertStringContainsString('id="kt_login_signin_form"', $html);
                        $this->assertStringContainsString('<dialog id="otpModal"', $html);
                        if ($scenario === 'content') {
                            $this->assertStringContainsString('<details class="login-mobile-updates">', $html);
                            $this->assertSame(1, substr_count($html, 'id="login-mobile-phrases"'));
                            $this->assertSame(1, substr_count($html, 'id="login-access4-phrases"'));
                        } else {
                            $this->assertStringNotContainsString('<details class="login-mobile-updates">', $html);
                        }
                    }
                }
            }
            File::put($directory . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->assertCount(36, $manifest);
        } finally {
            URL::forceRootUrl(null);
            if ($hadUri) {
                $_SERVER['REQUEST_URI'] = $previousUri;
            } else {
                unset($_SERVER['REQUEST_URI']);
            }
        }
    }
}
