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

    public function test_native_adapter_preserves_classic_sources_except_approved_blade_fixes(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode(
            file_get_contents($root . '/tests/fixtures/ui/native-contract.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($manifest['source_sha256'] as $file => $originalHash) {
            $content = file_get_contents($root . '/' . $file);

            if (str_ends_with($file, 'menu_lateral.blade.php')
                || str_ends_with($file, 'menu_superior.blade.php')) {
                // Reverte SOMENTE o ajuste de classes que corrige diretivas Blade
                // malformadas; qualquer mudança adicional no layout classico falha.
                $historical = str_ends_with($file, 'menu_lateral.blade.php')
                    ? '<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable @if(config(\'ui_theme.active\', false))ui-theme-refined @if(($uiResolvedTheme[\'dark\'] ?? false))ui-theme-refined--dark@endif @if(($uiResolvedTheme[\'density\'] ?? \'\') === \'compact\')ui-density-compact@endif @endif">'
                    : '<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed @if(config(\'ui_theme.active\', false))ui-theme-refined @if(($uiResolvedTheme[\'dark\'] ?? false))ui-theme-refined--dark@endif @if(($uiResolvedTheme[\'density\'] ?? \'\') === \'compact\')ui-density-compact@endif @endif>';

                $count = 0;
                $content = preg_replace_callback(
                    '/^<body id="kt_body" @class\\(\\[[\\s\\S]*?^\\s*\\]\\)>/m',
                    static function () use ($historical) { return $historical; },
                    $content,
                    1,
                    $count
                );
                self::assertSame(1, $count, 'Mudanca nao autorizada no body classico: ' . $file);
            }

            if (str_ends_with($file, 'menu_superior.blade.php')) {
                // O unico outro ajuste aprovado e o fechamento do bloco de
                // historico de releases (causa do ParseError no PHP compilado).
                $approved = [
                    "                        {{-- Não usar @forelse aqui: a compilação das views legadas\n"
                    . "                             não fechava o foreach gerado, gerando ParseError no PHP. --}}\n"
                    . "                        @if(count(\$appVersionHistoryGrouped ?? []) > 0)\n"
                    . "                            @foreach(\$appVersionHistoryGrouped as \$major => \$items)"
                        => "                        @forelse(\$appVersionHistoryGrouped as \$major => \$items)",
                    "                            @php\n                                \$majorId = 'top-major-' . \$major;\n                            @endphp"
                        => "                            @php(\$majorId = 'top-major-'.\$major)",
                    "                            @endforeach\n                        @else\n"
                    . "                            <div class=\"text-center text-muted\">Nenhuma versão interna registrada.</div>\n"
                    . "                        @endif"
                        => "                        @empty\n"
                        . "                            <div class=\"text-center text-muted\">Nenhuma versão interna registrada.</div>\n"
                        . "                        @endforelse",
                ];

                foreach ($approved as $current => $historical) {
                    self::assertSame(1, substr_count($content, $current), 'Bloco historico divergente: ' . $current);
                    $content = str_replace($current, $historical, $content);
                }
            }

            self::assertSame($originalHash, hash('sha256', $content),
                'Alteracao inesperada em codigo historico: ' . $file);
        }
    }
}
