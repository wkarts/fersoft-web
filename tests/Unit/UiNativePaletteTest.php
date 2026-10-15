<?php
namespace Tests\Unit;

use App\Support\UiThemePalette;
use PHPUnit\Framework\TestCase;

final class UiNativePaletteTest extends TestCase
{
    public function test_hex_never_accepts_css_or_script_payloads(): void
    {
        foreach (['red', '#fff', '#112233;position:fixed', 'url(javascript:alert(1))', '\"><script>', null, []] as $bad) {
            self::assertSame('#123456', UiThemePalette::hex($bad, '#123456'));
        }
        self::assertSame('#ABCDEF', UiThemePalette::hex('#abcdef', '#123456'));
    }

    public function test_palettes_produce_usable_contrast_and_only_safe_colors(): void
    {
        foreach (['#FFFFFF', '#14242C', '#59BDB0', '#168F81', '#2671E8', '#FFD600', '#333333'] as $accent) {
            foreach ([false, true] as $dark) {
                $tokens = UiThemePalette::tokens(['accent' => $accent, 'dark' => $dark, 'sidebar' => $dark ? '#101D2F' : '#FFFFFF']);
                foreach ($tokens as $value) self::assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', $value);
                self::assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($tokens['--ui-surface'], $tokens['--ui-ink']));
                self::assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($accent, $tokens['--ui-on-accent']));
                self::assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($tokens['--ui-aside'], $tokens['--ui-sidebar-ink']));
            }
        }
    }

    public function test_native_shell_retains_original_actions_and_alerts(): void
    {
        $root = dirname(__DIR__, 2);
        $classic = file_get_contents($root . '/resources/views/default/menu_lateral.blade.php');
        $native = file_get_contents($root . '/resources/views/themes/refined/layout.blade.php');
        preg_match_all('/<script[^>]+src=["\x27]([^"\x27]+)["\x27]/i', $classic, $scripts);
        foreach (array_unique($scripts[1]) as $src) {
            if ($src === '/metronic/js/scripts.bundle.js') continue;
            self::assertStringContainsString($src, $native, 'Missing original runtime script: ' . $src);
        }
        foreach (['global_delivery_alert', 'modal-alerta-sistema', 'modalWhatsAppGlobal', '__view_locais_select_menu_superior', '/login/logoff'] as $marker) {
            if (str_contains($classic, $marker)) self::assertStringContainsString($marker, $native);
        }
        self::assertStringContainsString('ui-legacy-widgets.js', $native);
        self::assertStringContainsString('id="ui-navigation"', $native);
        self::assertStringNotContainsString('id="kt_aside_menu"', $native);
        self::assertStringNotContainsString('id="kt_header_menu"', $native);
    }

    public function test_classic_menus_retain_full_reviewed_source_outside_theme_class_attribute(): void
    {
        // Source-level fingerprints cover every business control, action, menu,
        // modal, alert and script in both historical menus. Only the
        // explicitly validated @class() attribute is excluded, because the
        // old nested @if in that attribute caused an invalid compiled Blade.
        $root = dirname(__DIR__, 2);
        $fingerprints = [
            'resources/views/default/menu_lateral.blade.php'
                => 'd352f0e02f9b6204e19ce1bd3a3ea88e6370bdf8438e161e1a8b82a003a02e2a',
            'resources/views/default/menu_superior.blade.php'
                => '11c5a66b794581f597701d5c79d2d106824d4ee4449271ca58b3dd2d6e636e6c',
        ];

        foreach ($fingerprints as $file => $expected) {
            $source = file_get_contents($root . '/' . $file);
            self::assertIsString($source);
            self::assertStringContainsString("config('ui_theme.active', false)", $source);
            self::assertStringContainsString("'ui-theme-refined--dark' =>", $source);
            self::assertStringContainsString("'ui-density-compact' =>", $source);

            $count = 0;
            $normalized = preg_replace_callback(
                '/^<body id="kt_body" @class\(\[[\s\S]*?^[ \t]*\]\)>/m',
                static fn (): string => '<body id="kt_body" __REDACTED_FOR_CONTRACT__>',
                $source,
                1,
                $count
            );
            self::assertSame(1, $count, 'Missing or duplicate theme body: ' . $file);
            self::assertSame($expected, hash('sha256', $normalized),
                'Unreviewed legacy layout change: ' . $file);
        }

        // The original Metronic JS bundle is not edited; native widgets use
        // their own compatibility copy, so this vendor fingerprint is strict.
        $manifest = json_decode(file_get_contents(
            $root . '/tests/fixtures/ui/native-contract.json'
        ), true, 512, JSON_THROW_ON_ERROR);
        $bundle = 'public/metronic/js/scripts.bundle.js';
        self::assertSame($manifest['source_sha256'][$bundle], hash_file('sha256', $root . '/' . $bundle));
    }

}
