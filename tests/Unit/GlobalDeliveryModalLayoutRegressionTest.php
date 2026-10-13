<?php

namespace Tests\Unit;

use Tests\TestCase;

class GlobalDeliveryModalLayoutRegressionTest extends TestCase
{
    public function test_delivery_alert_is_rendered_inside_parent_body_slot_and_hidden_initially(): void
    {
        $layout = file_get_contents(resource_path('views/default/layout.blade.php'));
        $lateral = file_get_contents(resource_path('views/default/menu_lateral.blade.php'));
        $superior = file_get_contents(resource_path('views/default/menu_superior.blade.php'));

        $sectionPos = strpos($layout, "@section('global_delivery_alert')");
        $modalPos = strpos($layout, 'id="modalNovoPedidoAlerta"');

        $this->assertNotFalse($sectionPos);
        $this->assertNotFalse($modalPos);
        $this->assertLessThan($modalPos, $sectionPos);

        $this->assertStringContainsString(
            'style="display:none; z-index: 99999;"',
            $layout
        );

        $this->assertStringContainsString(
            "@yield('global_delivery_alert')",
            $lateral
        );
        $this->assertStringContainsString(
            "@yield('global_delivery_alert')",
            $superior
        );
    }

    public function test_primary_layouts_keep_original_favicons_when_historical_login_is_selected(): void
    {
        $lateral = file_get_contents(resource_path('views/default/menu_lateral.blade.php'));
        $superior = file_get_contents(resource_path('views/default/menu_superior.blade.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('imgs/favicon.png'));

        foreach ([$lateral, $superior] as $layout) {
            // A regra histórica está preservada, sem depender da nova classe.
            $this->assertStringContainsString("asset('favicon.ico')", $layout);
            $this->assertStringContainsString("asset('imgs/favicon.png')", $layout);
            $this->assertStringContainsString("filemtime(public_path('favicon.ico'))", $layout);
            $this->assertStringContainsString("config('login_presentation.page', 'access') === 'access_4'", $layout);
            $this->assertStringNotContainsString('login_presentation.enabled', $layout);
            $this->assertStringNotContainsString('login_presentation.global_branding_enabled', $layout);
            $this->assertStringContainsString("class_exists(", $layout);
            $this->assertStringContainsString("asset('imgs/slym.png')", $layout);
            $this->assertStringNotContainsString(
                '<link rel="shortcut icon" href="{{ asset(\'imgs/slym.png\') }}" />',
                $layout
            );
        }

        // PWA sempre disponível: a seleção histórica mantém o favicon original
        // e não precisa consultar o serviço de aparência.
        $this->assertStringContainsString("\$favicon = public_path('imgs/favicon.png')", $routes);
        $this->assertStringContainsString("class_exists(\\App\\Services\\LoginPresentationService::class)", $routes);
        $this->assertStringContainsString("config('login_presentation.page', 'access') === 'access_4'", $routes);
    }

    public function test_dynamic_favicon_is_single_and_server_resolved_only_for_access4(): void
    {
        foreach ([
            'views/default/menu_lateral.blade.php',
            'views/default/menu_superior.blade.php',
        ] as $path) {
            $layout = file_get_contents(resource_path($path));

            $this->assertStringContainsString("@if(!empty(\$appearanceAssets['favicon']))", $layout);
            $this->assertStringContainsString('<link rel="icon" type="image/png" href="{{ $appearanceAssets[\'favicon\'] }}">', $layout);
            $this->assertStringContainsString('->assetUrls()', $layout);
            $this->assertStringContainsString("config('login_presentation.page', 'access') === 'access_4'", $layout);

            // Sem alteração do HTML por script após a renderização.
            $this->assertStringNotContainsString('document.querySelector(\'link[rel="icon"]\')', $layout);
        }
    }
}
