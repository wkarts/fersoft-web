from pathlib import Path
import runpy, subprocess

p=Path('scripts/ci/build-native-ui.py');s=p.read_text()
if 'tokenize-ui-neutral-styles.py' not in s:
 s+='\n# Adapt neutral styles preserving the original fallback in authenticated pages.\nimport runpy\nrunpy.run_path(str(ROOT / "scripts/ci/tokenize-ui-neutral-styles.py"), run_name="__main__")\n'
marker='aria-label="Escolher tema"'
if 'aria-label="Central de suporte"' not in s:
 anchor='                <a href="{{ route(\'ui.themes.index\') }}" class="ui-icon-button" aria-label="Escolher tema">'
 new='''                @if(config('ui_theme.support_url') || config('ui_theme.support_phone'))
                    <button type="button" class="ui-icon-button" data-ui-support-open aria-label="Central de suporte"><i class="la la-question-circle" aria-hidden="true"></i></button>
                @endif
'''+anchor
 assert s.count(anchor)==1
 s=s.replace(anchor,new)
p.write_text(s)

p=Path('scripts/ui/build-preview.py');s=p.read_text()
if '"scripts/ci/tokenize-ui-neutral-styles.py"' not in s:
 assert s.count('    "scripts/ci/build-native-ui.py",')==1
 s=s.replace('    "scripts/ci/build-native-ui.py",','    "scripts/ci/build-native-ui.py",\n    "scripts/ci/tokenize-ui-neutral-styles.py",')
 p.write_text(s)

p=Path('app/Support/UiThemePalette.php');s=p.read_text()
if "'--ui-accent-ink'" not in s:
 s=s.replace('        $aside = self::hex', '''        $accentInk = $accent;
        for ($i = 1; self::contrast($surface, $accentInk) < 4.5 && $i <= 10; $i++) {
            $accentInk = self::mix($accent, $ink, $i / 10);
        }
        $aside = self::hex''')
 s=s.replace("            '--ui-accent-hover'", "            '--ui-accent-ink' => $accentInk,\n            '--ui-accent-hover'")
 p.write_text(s)
p=Path('tests/Unit/UiNativePaletteTest.php');s=p.read_text()
if "$tokens['--ui-accent-ink']" not in s:
 a="                self::assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($accent, $tokens['--ui-on-accent']));"
 assert s.count(a)==1
 s=s.replace(a,a+"\n                self::assertGreaterThanOrEqual(4.5, UiThemePalette::contrast($tokens['--ui-surface'], $tokens['--ui-accent-ink']));")
 p.write_text(s)
p=Path('public/css/ui-operational.css');s=p.read_text()
if '/* Custom accent contrast on surfaces. */' not in s:
 s+='''
/* Custom accent contrast on surfaces. */
body.ui-native-shell .ui-amount,body.ui-native-shell .ui-eyebrow,body.ui-native-shell .ui-chip,body.ui-native-shell .ui-widget-icon {color:var(--ui-accent-ink,var(--ui-accent));}
body.ui-native-shell .btn-light-primary,body.ui-native-shell .btn-light-success {color:var(--ui-accent-ink,var(--ui-accent))!important;}
body.ui-native-shell .ui-studio__apply {color:var(--ui-on-accent);}
body.ui-native-shell .ui-studio__publish,body.ui-native-shell .ui-studio__selected {color:var(--ui-accent-ink,var(--ui-accent));}
body.ui-native-shell .ui-studio__publish:hover {color:var(--ui-on-accent);}
'''
 p.write_text(s)
p=Path('.github/workflows/ui-native-browser.yml');s=p.read_text()
s=s.replace('vendor/bin/phpunit --filter UiNativePaletteTest', "vendor/bin/phpunit --filter 'UiNativePaletteTest|UiPreferencesIntegrationTest'")
if 'python tests/ui/test-neutral-styles.py' not in s:
 s=s.replace('          python scripts/ci/build-native-ui.py', '          python scripts/ci/build-native-ui.py\n          python tests/ui/test-neutral-styles.py')
p.write_text(s)
runpy.run_path('scripts/ci/tokenize-ui-neutral-styles.py',run_name='__main__')
subprocess.run(['git','add','--','tests/fixtures/ui/neutral-style-contracts.json'],check=True)
