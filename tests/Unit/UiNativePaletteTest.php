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
        $native = file_get_contents($root . '/resources/views/themes/refined/layout.blade.php')
            . file_get_contents($root . '/resources/views/themes/refined/partials/sidebar.blade.php')
            . file_get_contents($root . '/resources/views/themes/refined/partials/masthead.blade.php');
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

    public function test_dynamic_native_menu_keeps_all_current_legacy_navigation_and_visibility_contracts(): void
    {
        $root = dirname(__DIR__, 2);
        $classic = file_get_contents($root . '/resources/views/default/menu_lateral.blade.php');
        $superior = file_get_contents($root . '/resources/views/default/menu_superior.blade.php');
        $native = file_get_contents($root . '/resources/views/themes/refined/layout.blade.php')
            . file_get_contents($root . '/resources/views/themes/refined/partials/sidebar.blade.php')
            . file_get_contents($root . '/resources/views/themes/refined/partials/masthead.blade.php');

        foreach ([$classic, $superior] as $layout) {
            self::assertSame(1, substr_count($layout, '<body id="kt_body" @class(['));
            self::assertStringContainsString("'ui-theme-refined' => config('ui_theme.active', false)", $layout);
            self::assertStringContainsString("'ui-theme-refined--dark' =>", $layout);
            self::assertStringContainsString("'ui-density-compact' =>", $layout);
        }

        // Always compare to the CURRENT source. Main -> develop synchronization
        // may add modules; a frozen checksum would block legitimate upgrades.
        // The native shell must follow the existing permission-filtered menu.
        $start = strpos($classic, '<ul class="menu-nav menu-tour">');
        $end = strpos($classic, '<div class="d-flex flex-column flex-row-fluid wrapper"', $start);
        self::assertNotFalse($start, 'Original menu missing');
        self::assertNotFalse($end, 'Original navigation boundary missing');
        preg_match_all('/\bhref\s*=\s*["\x27]([^"\x27]+)["\x27]/', substr($classic, $start, $end - $start), $matches);
        $links = array_unique($matches[1]);
        self::assertGreaterThanOrEqual(25, count($links));
        foreach ($links as $href) {
            self::assertStringContainsString($href, $native, 'Missing authorized navigation: ' . $href);
        }

        // The historical release-notes UI remains present in the superior
        // menu and must compile through a properly closed @php / @foreach.
        self::assertStringContainsString('@foreach($appVersionHistoryGrouped as $major => $items)', $superior);
        self::assertStringContainsString("@php\n                                \$majorId", $superior);
        self::assertStringContainsString('@endforeach', $superior);

        // Vendor files are untouched. The copied adapter is intentionally
        // separate and is tested by the browser regression workflow.
        $manifest = json_decode(file_get_contents(
            $root . '/tests/fixtures/ui/native-contract.json'
        ), true, 512, JSON_THROW_ON_ERROR);
        $bundle = 'public/metronic/js/scripts.bundle.js';
        self::assertSame($manifest['source_sha256'][$bundle], hash_file('sha256', $root . '/' . $bundle));
    }

}
