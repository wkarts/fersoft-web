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

    public function test_primary_layouts_use_the_published_favicon_with_the_existing_canonical_default(): void
    {
        $lateral = file_get_contents(resource_path('views/default/menu_lateral.blade.php'));
        $superior = file_get_contents(resource_path('views/default/menu_superior.blade.php'));
        $service = file_get_contents(app_path('Services/LoginPresentationService.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        // O favicon institucional existente continua sendo a origem inicial,
        // sem obrigar a aplicação a exibir duas referências concorrentes.
        $this->assertFileExists(public_path('imgs/favicon.png'));
        $this->assertStringContainsString("'favicon' => 'imgs/favicon.png'", $service);
        $this->assertStringContainsString('->faviconPath()', $routes);

        $source = "app(\\App\\Services\\LoginPresentationService::class)->assetUrls()['favicon']";

        foreach ([$lateral, $superior] as $layout) {
            // O mesmo favicon publicado é utilizado nos dois layouts e no PWA.
            $this->assertStringContainsString($source, $layout);
            $this->assertSame(2, substr_count($layout, $source));
            $this->assertSame(1, substr_count($layout, '<link rel="icon" type="image/png"'));
            $this->assertSame(1, substr_count($layout, '<link rel="shortcut icon" type="image/png"'));

            // Não carregar o favicon antigo antes do ativo, nem restaurar
            // referências rígidas que impedem a troca pelo superusuário.
            $this->assertStringNotContainsString("asset('favicon.ico')", $layout);
            $this->assertStringNotContainsString("filemtime(public_path('favicon.ico'))", $layout);
            $this->assertStringNotContainsString(
                '<link rel="shortcut icon" href="{{ asset(\'imgs/slym.png\') }}" />',
                $layout
            );
        }
    }
}
