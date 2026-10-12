<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DiscreetScrollbarsTest extends TestCase
{
    public function test_scrollbars_are_small_and_keep_scrolling_enabled(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/public/css/style.css');

        $this->assertIsString($css);

        $marker = '/* Rolagem discreta da área de conteúdo (sidebar intocada) */';
        $this->assertStringContainsString($marker, $css);
        $contentRules = substr($css, strpos($css, $marker));

        $this->assertStringContainsString('html::-webkit-scrollbar', $contentRules);
        $this->assertStringContainsString('#kt_wrapper *::-webkit-scrollbar', $contentRules);
        $this->assertStringContainsString('width: 4px;', $contentRules);
        $this->assertStringContainsString('height: 4px;', $contentRules);
        $this->assertStringContainsString('scrollbar-width: thin;', $contentRules);
        $this->assertStringContainsString('background: transparent;', $contentRules);
        $this->assertStringContainsString('#kt_wrapper .ps__thumb-y', $contentRules);
        $this->assertStringContainsString('#kt_wrapper .ps__thumb-x', $contentRules);

        // Aparência somente: não alterar comportamento de overflow ou scroll.
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*overflow(?:-x|-y)?\s*:/m',
            $contentRules
        );
        $this->assertStringNotContainsString('#kt_aside::-webkit-scrollbar', $contentRules);
        $this->assertStringNotContainsString('#kt_aside *::-webkit-scrollbar', $contentRules);
    }

    public function test_existing_sidebar_scrollbar_remains_hidden(): void
    {
        $layout = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/default/menu_lateral.blade.php'
        );

        $this->assertIsString($layout);
        $this->assertStringContainsString('.hide-overflow::-webkit-scrollbar', $layout);
        $this->assertStringContainsString('scrollbar-width: none;', $layout);
        $this->assertStringContainsString(
            'class="aside aside-left aside-fixed d-flex flex-column flex-row-auto hide-overflow" id="kt_aside" style="overflow-y: auto;"',
            $layout
        );
    }

    public function test_existing_layouts_reuse_one_cache_versioned_stylesheet(): void
    {
        foreach ([
            'default/menu_lateral.blade.php',
            'default/menu_superior.blade.php',
        ] as $path) {
            $layout = file_get_contents(
                dirname(__DIR__, 2) . '/resources/views/' . $path
            );

            $this->assertIsString($layout, $path);
            $this->assertStringContainsString(
                "/css/style.css?v={{ filemtime(public_path('css/style.css')) }}",
                $layout,
                $path
            );
        }
    }
}
