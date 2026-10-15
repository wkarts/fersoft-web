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

    public function test_classic_menu_sources_remain_byte_identical(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode(file_get_contents($root . '/tests/fixtures/ui/native-contract.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($manifest['source_sha256'] as $file => $hash) self::assertSame($hash, hash_file('sha256', $root . '/' . $file), $file);
    }
}
