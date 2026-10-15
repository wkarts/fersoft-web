from pathlib import Path
r=Path.cwd()
g=r/'scripts/ci/build-native-ui.py'
s=g.read_text()
anchor="legacy=read('resources/views/default/menu_lateral.blade.php')"
insert="""# Blade directives need a word boundary before @endif. Correct only UI opt-in
# tokens introduced by the previous theme work, not the classic business markup.
for p in ['resources/views/default/menu_lateral.blade.php', 'resources/views/default/menu_superior.blade.php', 'resources/views/themes/refined/studio/index.blade.php']:
 s=read(p)
 fixed=s.replace('ui-theme-refined--dark@endif', 'ui-theme-refined--dark @endif').replace('ui-density-compact@endif', 'ui-density-compact @endif').replace('ui-studio__theme--selected@endif', 'ui-studio__theme--selected @endif')
 if fixed != s: write(p, fixed)
"""
if '# Blade directives need a word boundary' not in s:
 assert s.count(anchor)==1
 s=s.replace(anchor,insert+anchor)
 start=s.index("layout='''");end=s.index('for key,val',start)
 s=s[:start]+s[start:end].replace('--dark@endif','--dark @endif').replace('ui-density-compact@endif','ui-density-compact @endif')+s[end:]
 start=s.index('# POS pages');t=s[start:]
 t=t.replace("s=read(p)\n if 'ui-pos-refined' in s: continue", "s=read(p)\n if 'ui-pos-refined' in s:\n  write(p, s.replace('ui-theme-refined--dark@endif', 'ui-theme-refined--dark @endif').replace('ui-density-compact@endif', 'ui-density-compact @endif'))\n  continue")
 t=t.replace("($uiResolvedTheme['dark'] ?? false)ui-theme-refined--dark@endif", "($uiResolvedTheme['dark'] ?? false)ui-theme-refined--dark @endif").replace("=== 'compact')ui-density-compact@endif @endif", "=== 'compact')ui-density-compact @endif @endif")
 s=s[:start]+t
 g.write_text(s)
p=r/'tests/ui/render-native.php';s=p.read_text();a="$palettes=config('ui_theme.presets');"
b="""// Prove every render exists; the legacy exception handler can exit CLI with zero.
register_shutdown_function(static function () use ($target): void {
    if (count(glob($target . '/fer-*.html')) !== 12) {
        fwrite(STDERR, 'Rendering incomplete: expected 12 actual Blade fixtures.' . PHP_EOL);
        exit(1);
    }
});
file_put_contents($target . '/native-compiled.php', $app['blade.compiler']->compileString(file_get_contents(resource_path('views/themes/refined/layout.blade.php'))));
$palettes=config('ui_theme.presets');"""
if 'register_shutdown_function' not in s:
 assert s.count(a)==1
 s=s.replace(a,b);p.write_text(s)
