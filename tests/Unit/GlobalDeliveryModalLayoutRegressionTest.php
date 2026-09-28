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

    public function test_primary_layouts_use_existing_canonical_favicon_assets(): void
    {
        $lateral = file_get_contents(resource_path('views/default/menu_lateral.blade.php'));
        $superior = file_get_contents(resource_path('views/default/menu_superior.blade.php'));

        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('imgs/favicon.png'));

        foreach ([$lateral, $superior] as $layout) {
            $this->assertStringContainsString(
                "asset('favicon.ico')",
                $layout
            );
            $this->assertStringContainsString(
                "asset('imgs/favicon.png')",
                $layout
            );
            $this->assertStringContainsString(
                "filemtime(public_path('favicon.ico'))",
                $layout
            );
            $this->assertStringNotContainsString(
                "asset('imgs/slym.png')",
                $layout
            );
        }
    }
}
