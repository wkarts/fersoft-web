<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppInstallationTest extends TestCase
{
    public function test_installation_endpoints_are_unavailable_when_disabled(): void
    {
        config()->set('app.pwa_enabled', false);

        $this->get('/app-install/manifest.webmanifest')->assertNotFound();
        $this->get('/app-install/icon/180.png')->assertNotFound();
        $this->get('/app-install/icon/192.png')->assertNotFound();
        $this->get('/app-install/icon/512.png')->assertNotFound();
    }

    public function test_manifest_reuses_the_configured_application_name_and_favicon(): void
    {
        if (!function_exists('imagecreatefrompng')) {
            $this->markTestSkipped('O dimensionamento do favicon requer PHP GD.');
        }

        config()->set('app.pwa_enabled', true);
        config()->set('app.name', 'Aplicação do Cliente');

        $response = $this->get('/app-install/manifest.webmanifest');
        $response->assertOk()->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8');

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
        foreach ($manifest['icons'] as $icon) {
            $this->assertSame('image/png', $icon['type']);
            $this->assertStringStartsWith('/app-install/icon/', $icon['src']);
        }
    }

    public function test_native_installation_icons_are_valid_square_png_files(): void
    {
        if (!function_exists('imagecreatefrompng')) {
            $this->markTestSkipped('O dimensionamento do favicon requer PHP GD.');
        }

        config()->set('app.pwa_enabled', true);
        $this->assertFileExists(public_path('imgs/favicon.png'));

        foreach ([180, 192, 512] as $size) {
            $response = $this->get("/app-install/icon/{$size}.png");
            $response->assertOk()->assertHeader('Content-Type', 'image/png');
            $image = getimagesizefromstring($response->getContent());
            $this->assertIsArray($image);
            $this->assertSame($size, $image[0]);
            $this->assertSame($size, $image[1]);
            $this->assertSame('image/png', $image['mime']);
        }

        $this->get('/app-install/icon/1024.png')->assertNotFound();
    }

    public function test_login_and_erp_layouts_reuse_the_same_conditional_metadata(): void
    {
        $partial = file_get_contents(resource_path('views/layouts/installable.blade.php'));
        $this->assertStringContainsString("config('app.pwa_enabled', false)", $partial);
        $this->assertStringContainsString("config('app.name')", $partial);
        $this->assertStringContainsString("route('app-install.manifest'", $partial);
        $this->assertStringContainsString("route('app-install.icon'", $partial);

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
    }
}
