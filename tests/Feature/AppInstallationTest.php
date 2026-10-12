<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppInstallationTest extends TestCase
{
    public function test_manifest_is_always_available_even_if_old_feature_flag_was_false(): void
    {
        config()->set('login_presentation.enabled', false);
        config()->set('login_presentation.global_branding_enabled', false);
        // Instalações antigas ainda podem ter PWA_ENABLED=false no .env.
        // O código não deve consultar mais essa chave.
        config()->set('app.pwa_enabled', false);
        config()->set('app.name', 'Aplicação do Cliente');

        $response = $this->get('/app-install/manifest');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8');

        $manifest = $response->json();
        $this->assertSame(config('app.name'), $manifest['name']);
        $this->assertSame(config('app.name'), $manifest['short_name']);
        $this->assertSame('/', $manifest['id']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('pt-BR', $manifest['lang']);
        $this->assertFalse($manifest['prefer_related_applications']);

        $this->assertCount(2, $manifest['icons']);
        $this->assertSame('192x192', $manifest['icons'][0]['sizes']);
        $this->assertSame('512x512', $manifest['icons'][1]['sizes']);
        foreach ([192, 512] as $index => $size) {
            $icon = $manifest['icons'][$index];
            $this->assertSame('image/png', $icon['type']);
            $this->assertSame('any', $icon['purpose']);
            $this->assertSame("/app-install/icon/{$size}", parse_url($icon['src'], PHP_URL_PATH));
            $this->assertStringNotContainsString('.png', parse_url($icon['src'], PHP_URL_PATH));
        }
    }

    public function test_icons_are_generated_from_current_favicon_even_with_old_flag_disabled(): void
    {
        config()->set('login_presentation.enabled', false);
        config()->set('login_presentation.global_branding_enabled', false);
        if (!function_exists('imagecreatefrompng')) {
            $this->markTestSkipped('Extensão GD necessária para redimensionar o favicon.');
        }

        config()->set('app.pwa_enabled', false);
        $this->assertFileExists(public_path('imgs/favicon.png'));

        foreach ([180, 192, 512] as $size) {
            $response = $this->get("/app-install/icon/{$size}");
            $response->assertOk()->assertHeader('Content-Type', 'image/png');
            $image = getimagesizefromstring($response->getContent());
            $this->assertIsArray($image);
            $this->assertSame($size, $image[0]);
            $this->assertSame($size, $image[1]);
            $this->assertSame('image/png', $image['mime']);
        }

        $this->get('/app-install/icon/1024')->assertNotFound();
    }

    public function test_service_worker_is_available_from_root_scope_without_offline_data_cache(): void
    {
        $response = $this->get('/app-install/worker');
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->assertHeader('Service-Worker-Allowed', '/');

        $source = $response->getContent();
        $this->assertStringContainsString("addEventListener('install'", $source);
        $this->assertStringContainsString("addEventListener('fetch'", $source);
        $this->assertStringNotContainsString('caches.open', $source);
        $this->assertStringNotContainsString('cache.add', $source);
        $this->assertStringNotContainsString('respondWith', $source);
    }

    public function test_old_manifest_and_icon_urls_are_redirected_when_handled_by_laravel(): void
    {
        $this->get('/app-install/manifest.webmanifest')
            ->assertStatus(307)
            ->assertRedirect(route('app-install.manifest'));

        $this->get('/app-install/icon/192.png')
            ->assertStatus(307)
            ->assertRedirect(route('app-install.icon', ['size' => 192]));
    }

    public function test_all_login_and_erp_layouts_reuse_one_unconditional_installation_partial(): void
    {
        $partial = file_get_contents(resource_path('views/layouts/installable.blade.php'));
        $this->assertStringContainsString("config('app.name')", $partial);
        $this->assertStringContainsString("route('app-install.manifest'", $partial);
        $this->assertStringContainsString("route('app-install.icon'", $partial);
        $this->assertStringContainsString("route('app-install.worker'", $partial);
        $this->assertStringContainsString('navigator.serviceWorker.register', $partial);
        $this->assertStringNotContainsString("config('app.pwa_enabled'", $partial);
        $this->assertStringNotContainsString('PWA_ENABLED', $partial);
        $this->assertStringNotContainsString('@if', $partial);

        foreach ([
            'default/menu_lateral.blade.php',
            'default/menu_superior.blade.php',
            'layouts/app.blade.php',
            'login/access.blade.php',
            'login/access_2.blade.php',
            'login/access_3.blade.php',
            'login/login.blade.php',
            'login/cadastro.blade.php',
            'login/novo_parceiro.blade.php',
            'login/plano.blade.php',
        ] as $path) {
            $source = file_get_contents(resource_path('views/' . $path));
            $this->assertStringContainsString("@include('layouts.installable')", $source, $path);
        }

        $appConfig = file_get_contents(config_path('app.php'));
        $sampleEnv = file_get_contents(base_path('.env.example'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringNotContainsString('PWA_ENABLED', $appConfig);
        $this->assertStringNotContainsString('pwa_enabled', $appConfig);
        $this->assertStringNotContainsString('PWA_ENABLED', $sampleEnv);
        $this->assertStringNotContainsString("config('app.pwa_enabled'", $routes);

        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertSame(0, substr_count($appLayout, "navigator.serviceWorker.register('js/serviceworker.js')"));
    }
}
