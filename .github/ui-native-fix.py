from pathlib import Path

def replace(path, before, after):
 p=Path(path);s=p.read_text()
 if before not in s:
  assert after in s, path
  return
 assert s.count(before)==1, path
 p.write_text(s.replace(before,after))

p=Path('public/css/ui-operational.css');s=p.read_text()
marker='/* Operational header and real modal hit targets. */'
if marker not in s:
 s+='''
/* Operational header and real modal hit targets. */
body.ui-native-shell .ui-actions {display:flex;align-items:center;justify-content:space-between;gap:10px 16px;flex-wrap:wrap;width:100%;}
body.ui-native-shell .ui-actions > .container-fluid {width:auto;flex:1 1 560px;min-width:0;}
body.ui-native-shell .ui-actions > .topbar {flex:0 0 auto;margin-left:auto;}
body.ui-native-shell .ui-widget-icon i,body.ui-native-shell .ui-footer-link i,body.ui-native-shell .ui-icon-button i {color:inherit;font-size:inherit;}
body.ui-native-shell .ui-session-extra i {color:inherit!important;}
body.ui-native-shell .ui-session-extra {font-size:10px!important;}
body.ui-native-shell .ui-actions .menu-nav > .menu-item a[href*="/ui/temas"] {color:var(--ui-muted)!important;background:var(--ui-surface)!important;border:1px solid var(--ui-border);}
body.ui-native-shell .modal-header .close {min-width:32px;min-height:32px;line-height:1;display:inline-flex;align-items:center;justify-content:center;border-radius:7px;color:inherit;}
body.ui-native-shell .modal-header .close:focus-visible {outline:2px solid var(--ui-accent);outline-offset:2px;}
body.ui-native-shell .ui-skip {top:0;left:12px;}
body.ui-native-shell .ui-mobile-brand {display:none;}
@media(max-width:991px) {
 body.ui-native-shell .ui-masthead {flex-wrap:wrap;gap:8px;}
 body.ui-native-shell .ui-mobile-brand {display:flex;align-items:center;flex:1;min-width:0;}
 body.ui-native-shell .ui-mobile-brand img {display:block;max-width:125px;max-height:35px;width:auto;height:auto;object-fit:contain;}
 body.ui-native-shell .ui-masthead-tools {margin-left:auto;flex-direction:row;}
 body.ui-native-shell .ui-operational-actions {order:3;flex:1 1 100%;width:100%;}
 body.ui-native-shell .ui-actions > .container-fluid {flex:1 1 100%;}
 body.ui-native-shell .ui-actions #ui-actions-menu-wrapper {width:100%;}
 body.ui-native-shell .ui-actions .menu-nav {width:100%;gap:6px;}
 body.ui-native-shell .ui-actions .menu-item {flex:0 1 auto;min-width:0;max-width:100%;}
 body.ui-native-shell .ui-actions .menu-item.ml-1 {flex:1 1 100%;}
 body.ui-native-shell .ui-actions .input-group {max-width:none;width:100%;}
 body.ui-native-shell .ui-actions > .topbar {width:100%;justify-content:flex-end;gap:8px;}
 body.ui-native-shell .ui-session {gap:5px 10px;}
}
'''
 p.write_text(s)
replace('scripts/ci/build-native-ui.py', '            <div class="ui-operational-actions">\n__ACTIONS__', '            <div class="ui-mobile-brand">\n__MOBILEBRAND__\n            </div>\n            <div class="ui-operational-actions">\n__ACTIONS__')
replace('scripts/ci/build-native-ui.py','dict(HEAD=head,BRAND=brand,VERSION=version','dict(HEAD=head,BRAND=brand,MOBILEBRAND=brand,VERSION=version')
replace('public/js/ui-operational.js', "        entry.link.setAttribute('aria-expanded', String(expanded));", """        entry.link.setAttribute('aria-expanded', String(expanded));
        entry.submenu.style.removeProperty('transform');
        if (expanded && desktop.matches && body.dataset.uiLayout === 'superior') {
            const rect = entry.submenu.getBoundingClientRect();
            let offset = Math.min(0, window.innerWidth - 12 - rect.right);
            if (rect.left + offset < 12) offset = 12 - rect.left;
            if (offset) entry.submenu.style.transform = 'translateX(' + offset + 'px)';
        }""")
replace('public/js/ui-operational.js', "    desktop.addEventListener('change', changeViewport);", """    desktop.addEventListener('change', changeViewport);
    let resizeTick = 0;
    window.addEventListener('resize', () => {
        cancelAnimationFrame(resizeTick);
        resizeTick = requestAnimationFrame(() => toggles.filter(entry => !entry.submenu.hidden)
            .forEach(entry => setGroup(entry, true)));
    });""")
replace('tests/ui/native-browser.py', '(e)=>jQuery(e).data("KTDatatable") != null', '(e)=>jQuery(e).closest(".datatable").data("KTDatatable") != null')
replace('tests/Unit/UiNativePaletteTest.php', 'test_classic_menu_sources_remain_byte_identical', 'test_native_adapter_uses_current_classic_sources_without_mutating_them')
replace('tests/ui/render-native.php', "'fersoft.ui_theme.light'", "'fersoft.ui_theme.1:1:0:light'")
replace('app/Services/UiThemeService.php', """        $key = 'fersoft.ui_theme.' . ($legacyDark === null ? 'auto' : ($legacyDark ? 'dark' : 'light'));
        $cached = $request->attributes->get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $session = $request->session()->get('user_logged', []);""", "        $session = $request->hasSession() ? $request->session()->get('user_logged', []) : [];")
replace('app/Services/UiThemeService.php', "        $super = (bool) ($context['super'] ?? false);", """        $super = (bool) ($context['super'] ?? false);
        // Scope cached values to company, user, privilege and requested color mode.
        $key = 'fersoft.ui_theme.' . $companyId . ':' . $userId . ':' . (int) $super
            . ':' . ($legacyDark === null ? 'auto' : ($legacyDark ? 'dark' : 'light'));
        $cached = $request->attributes->get($key);
        if (is_array($cached)) {
            return $cached;
        }""")
replace('app/Services/UiThemeService.php', "        $colorMode = 'follow';", "        $hasPersonalChoice = false;\n        $colorMode = 'follow';")
replace('app/Services/UiThemeService.php', "                        $slug = $preference->theme_slug;", "                        $slug = $preference->theme_slug;\n                        $hasPersonalChoice = true;")
replace('app/Services/UiThemeService.php', ": ($colorMode === 'light' ? false : ($themeDark || (bool) $legacyDark));", ": ($colorMode === 'light' ? false\n                : ($hasPersonalChoice ? $themeDark : ($themeDark || (bool) $legacyDark)));")
