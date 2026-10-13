<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LoginResponsiveRulesTest extends TestCase
{
    public function test_compact_login_keeps_published_content_without_the_decorative_panel(): void
    {
        $root = dirname(__DIR__, 2);
        $css = file_get_contents($root . '/public/css/login-access4.css');
        $form = file_get_contents($root . '/resources/views/login/components/access4/form.blade.php');
        $contents = file_get_contents($root . '/resources/views/login/components/access4/contents.blade.php');
        $js = file_get_contents($root . '/public/js/login-access4.js');

        $this->assertStringNotContainsString('right: -25%', $css);
        $this->assertStringContainsString('.login-access4 .login-public { display: none; }', $css);
        $this->assertStringContainsString('<details class="login-mobile-updates">', $form);
        $this->assertStringContainsString("'phraseId' => 'login-mobile-phrases'", $form);
        $this->assertStringContainsString("\$phraseId ?? 'login-access4-phrases'", $contents);
        $this->assertStringContainsString("querySelectorAll('[data-login-carousel]')", $js);
        $this->assertStringContainsString('login-flash--info', $form);
    }

    public function test_editor_preview_has_separate_desktop_and_mobile_viewports(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/public/js/login-appearance-admin.js');
        $this->assertStringContainsString('desktop: {width: 1200, height: 800', $source);
        $this->assertStringContainsString('mobile: {width: 390, height: 780', $source);
        $this->assertStringContainsString('new ResizeObserver(resizePreview)', $source);
        $this->assertStringContainsString('data-preview-device', file_get_contents(dirname(__DIR__, 2) . '/tests/Browser/access4_responsive.py'));
    }
}
