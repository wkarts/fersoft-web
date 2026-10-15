"""Development-only codemod. Emits native UI files; never used by client installations."""
from pathlib import Path
import json,re,hashlib
ROOT=Path(__file__).resolve().parents[2]
def read(p): return (ROOT/p).read_text()
def write(p,s):
 f=ROOT/p;f.parent.mkdir(parents=True,exist_ok=True);f.write_text(s)
def replace_once(s,a,b):
 assert s.count(a)==1, (a[:90],s.count(a));return s.replace(a,b,1)
def add_item_class(markup, marker, class_name):
 assert markup.count(marker)==1, (marker, markup.count(marker))
 marker_at=markup.index(marker)
 item_at=markup.rfind('<li',0,marker_at)
 tag_end=markup.find('>',item_at)+1
 assert item_at>=0 and tag_end>item_at, (marker, item_at, tag_end)
 opening=markup[item_at:tag_end]
 class_match=re.search(r'class="([^"]*)"',opening)
 assert class_match, opening
 if class_name in class_match.group(1).split(): return markup
 updated=opening[:class_match.start(1)]+class_match.group(1)+' '+class_name+opening[class_match.end(1):]
 return markup[:item_at]+updated+markup[tag_end:]
# Blade directives need a word boundary before @endif. Correct only UI opt-in
# tokens introduced by the previous theme work, not the classic business markup.
for p in ['resources/views/default/menu_lateral.blade.php', 'resources/views/default/menu_superior.blade.php', 'resources/views/themes/refined/studio/index.blade.php']:
 s=read(p)
 fixed=s.replace('ui-theme-refined--dark@endif', 'ui-theme-refined--dark @endif').replace('ui-density-compact@endif', 'ui-density-compact @endif').replace('ui-studio__theme--selected@endif', 'ui-studio__theme--selected @endif')
 if fixed != s: write(p, fixed)
legacy=read('resources/views/default/menu_lateral.blade.php')
# Preserve all business controls, php blocks, event handlers, alerts and assets.
head=legacy[legacy.index("    @include('layouts.installable')"):legacy.index('</head>')]
head=re.sub(r'<style\b[^>]*>.*?</style>','',head,flags=re.S|re.I)
a=head.index('    @if($tema_menu == 1)');b=head.index('    @endif',a)+len('    @endif')
head=head[:a]+'    <link href="/metronic/css/style.bundle.css" rel="stylesheet" type="text/css">'+head[b:]
head=re.sub(r'^.*<link[^>]+(?:light-menu|dark-aside|dark-brand|/css/extend\.css|/metronic/css/light\.css|fonts\.googleapis\.com)[^>]*>.*\n','',head,flags=re.M)
head=head.replace("@include('themes.refined.assets', ['uiResolvedTheme' => $uiResolvedTheme])", "@include('themes.refined.assets', ['uiResolvedTheme' => $uiResolvedTheme])")
head = head.replace('https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css', '/css/line-awesome.min.css')
head+='\n    <link rel="stylesheet" href="{{ asset(\'css/ui-operational.css\') }}?v={{ filemtime(public_path(\'css/ui-operational.css\')) }}">\n    @stack(\'styles\')\n'
# Logo and version source code remain exact, no synthetic brand or name.
a=legacy.index('                <a href="/graficos" class="brand-logo">');b=legacy.index('                <button class="brand-toggle',a)
brand=legacy[a:b]
a=legacy.index('            @php\n                $appVersionValue');b=legacy.index('            <div class="aside-menu-wrapper',a)
version=legacy[a:b].replace('d-none d-lg-block px-4 pt-3 pb-2 app-version-desktop-wrapper','ui-version app-version-desktop-wrapper')
a=legacy.index('                    <ul class="menu-nav menu-tour">');b=legacy.index('\n        <div class="d-flex flex-column flex-row-fluid wrapper"',a)
nav=legacy[a:b]
nav=nav[:nav.rfind('</ul>')+5] # all closing Blade directives are within list
nav=nav.replace('<br><br>','')
nav=re.sub(r'\sdata-menu-toggle="[^"]*"','',nav)
nav=re.sub(r'\sdata-menu-dropdown-timeout="[^"]*"','',nav)
# No Metronic menu initialization on our container. Existing classes for modules retained.
nav=nav.replace('class="menu-nav menu-tour"','class="ui-nav-list menu-tour"')
nav=nav.replace('style="background: #000"','')
a=legacy.index('            <div id="kt_header"');b=legacy.index('            <div id="kt_content"',a)
actions=legacy[a:b]
# Mark the original controls without changing their routes or handlers.
for marker,class_name in [
 ("Empresa: {{session('user_logged')['empresa_nome']}}",'ui-header-company'),
 ('id="ambiente-tour"','ui-header-environment'),
 ('data-target="#modal-tema"','ui-header-theme'),
 ('__view_connect_api_whatsapp_modal_button()','ui-header-whatsapp'),
 ('__view_locais_select_menu_superior()','ui-header-unit'),
]: actions=add_item_class(actions,marker,class_name)
# Only native shell owns positioning; bootstrap dropdowns/widgets remain functional.
actions=actions.replace('id="kt_header" class="header header-fixed"','id="ui-actions" class="ui-actions"')
actions=actions.replace('id="kt_header_menu_wrapper"','id="ui-actions-menu-wrapper"').replace('id="kt_header_menu"','id="ui-actions-menu"')
actions=actions.replace('id="kt_scrolltop"','id="ui-scrolltop"')
# Native appearance opens the catalog, not the old global-theme modal.
actions=actions.replace('data-toggle="modal" href="#!" data-target="#modal-tema"', "href=\"{{ route('ui.themes.index') }}\"")
actions=replace_once(actions,"Empresa: {{session('user_logged')['empresa_nome']}}",'<span class="ui-company-label">EMPRESA ATIVA</span><strong class="ui-company-name">{{session(\'user_logged\')[\'empresa_nome\']}}</strong>')
actions=replace_once(actions,"Ambiente: {{session('user_logged')['ambiente']}}",'<i class="la la-check ui-environment-check" aria-hidden="true"></i><span>{{session(\'user_logged\')[\'ambiente\']}}</span>')
# Counter accounts retain one theme action in the same header group.
actions=replace_once(actions,'                                    @endif\n\n                                    @if($contrato == 0)','                                    @endif\n\n                                    @if(session(\'tipo_contador\'))\n                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active ui-header-theme">\n                                            <a href="{{ route(\'ui.themes.index\') }}" class="label label-xl label-inline"><i class="la la-palette" aria-hidden="true"></i><span>Tema</span></a>\n                                        </li>\n                                    @endif\n\n                                    @if($contrato == 0)')
theme_match=re.search('<a\\b[^>]*route\\([^>]*ui\\.themes\\.index[^>]*>\\s*Tema\\s*</a>',actions,re.S)
assert theme_match, 'Theme link is missing from the original header actions.'
theme_link=theme_match.group(0)
tag_end=theme_link.index('>')+1
closing=theme_link.rfind('</a>')
theme_link=theme_link[:tag_end]+'<i class="la la-palette" aria-hidden="true"></i><span>Tema</span>'+theme_link[closing:]
actions=actions.replace(theme_match.group(0),theme_link,1)
actions=replace_once(actions,'<span class="kt-header__topbar-welcome kt-hidden-mobile">Olá,</span>','<span class="kt-header__topbar-welcome kt-hidden-mobile">Olá,</span><span class="ui-user-avatar" aria-hidden="true">{{ \\Illuminate\\Support\\Str::upper(\\Illuminate\\Support\\Str::substr((string) session(\'user_logged.nome\', \'?\'), 0, 1)) }}</span>')
# Exactly the actions in the original template, not a rewritten subset.
a=legacy.index('                <div id="kt_subheader"');b=legacy.index('\n                </div>',legacy.index('{!! __view_datahora_clock() !!}',a))+len('\n                </div>')
session=legacy[a:b].replace('id="kt_subheader" class="subheader py-2 py-lg-4  subheader-solid "','id="ui-session" class="ui-session"')
# Mark original widgets classes, values are preserved. Avoid hidden essential session data in desktop.
session=session.replace('class="topbar-item not-mobile" style="display: none"','class="topbar-item ui-session-extra"')
a=legacy.index('        <div id="box_whatsapp"');b=legacy.index('<!--\n\t\t\t@if(env("CONTATO_SUPORTE")',a)
contact=legacy[a:b]
a=legacy.index('<div class="modal fade" id="modal-dinamic"');b=legacy.rindex('</body>')
runtime=legacy[a:b]
# Keep dependency for business widgets; prevent only the old layout's auto initialization.
runtime=runtime.replace('src="/metronic/js/scripts.bundle.js"','src="/js/ui-legacy-widgets.js"')
# Keep the original Hub support integration and its setup script intact.
# The approved HTML has no command palette or module-search shortcut.
layout='''<!DOCTYPE html>
<html lang="pt-BR">
<head>
__HEAD__
</head>
@php
    $uiPreferredMenu = $uiResolvedTheme['menu_mode'] ?? 'follow';
    $uiMenuMode = in_array($uiPreferredMenu, ['lateral', 'superior'], true) ? $uiPreferredMenu : (($tipoMenu ?? 'lateral') === 'superior' ? 'superior' : 'lateral');
    $uiIdentity = (string) (session('user_logged.empresa', 0)) . ':' . (string) session('user_logged.id', 0);
@endphp
<body id="kt_body" class="ui-theme-refined ui-native-shell @if($uiResolvedTheme['dark'] ?? false)ui-theme-refined--dark @endif @if(($uiResolvedTheme['density'] ?? '') === 'compact')ui-density-compact @endif"
      data-ui-layout="{{ $uiMenuMode }}" data-ui-identity="{{ $uiIdentity }}" data-ui-theme="{{ $uiResolvedTheme['slug'] ?? 'fer-light' }}">
<a class="ui-skip" href="#kt_content">Ir para o conteúdo</a>
<div class="ui-app">
    <aside id="ui-sidebar" class="ui-sidebar" aria-label="Navegação da aplicação">
        <div class="ui-brand">
__BRAND__
            <button type="button" class="ui-icon-button" data-ui-collapse aria-label="Recolher navegação" aria-expanded="true">«</button>
            <button type="button" class="ui-icon-button ui-mobile-close" data-ui-menu-close aria-label="Fechar navegação">×</button>
        </div>
__VERSION__
        <nav id="ui-navigation" class="ui-navigation" aria-label="Módulos do sistema">
            <p class="ui-nav-caption">Áreas da aplicação</p>
__NAV__
        </nav>
        @if(config('ui_theme.support_url') || config('ui_theme.support_phone'))
        <div class="ui-sidebar-footer">
            <button type="button" class="ui-footer-link ui-support-link" data-ui-support-open>
                <i class="la la-question-circle" aria-hidden="true"></i><span>Central de suporte</span><span class="ui-support-indicator" aria-hidden="true"></span>
            </button>
        </div>
        @endif
    </aside>
    <div class="ui-menu-backdrop" data-ui-menu-close hidden></div>
    <div id="kt_wrapper" class="ui-workspace">
        <header class="ui-masthead">
            <button type="button" class="ui-icon-button ui-mobile-trigger" data-ui-menu-open aria-label="Abrir navegação" aria-controls="ui-sidebar" aria-expanded="false">☰</button>
            <div class="ui-mobile-brand">
__MOBILEBRAND__
            </div>
            <div class="ui-operational-actions">
__ACTIONS__
            </div>
        </header>
__SESSION__
        <main id="kt_content" class="ui-page" tabindex="-1">
            @yield('content')
        </main>
    </div>
</div>
__CONTACT__
__RUNTIME__
@stack('scripts')
<script src="{{ asset('js/ui-operational.js') }}?v={{ filemtime(public_path('js/ui-operational.js')) }}" defer></script>
</body>
</html>
'''
for key,val in dict(HEAD=head,BRAND=brand,MOBILEBRAND=brand,VERSION=version,NAV=nav,ACTIONS=actions,SESSION=session,CONTACT=contact,RUNTIME=runtime).items():
 layout=replace_once(layout,'__'+key+'__',val)
layout='\n'.join(line.rstrip() for line in layout.splitlines())+'\n'
write('resources/views/themes/refined/layout.blade.php',layout)
for p in ['resources/views/default/layout.blade.php','resources/views/contabilidade/configuracoes.blade.php']:
 s=read(p)
 if "'themes.refined.layout'" in s: continue
 write(p,replace_once(s,"@extends('default/menu_'.$tipoMenu)","@extends(config('ui_theme.active', false) ? 'themes.refined.layout' : 'default/menu_'.$tipoMenu)"))
# The complete KT library still provides KTDatatable, KTWizard, etc; only shell initializers disabled.
bundle=read('public/metronic/js/scripts.bundle.js')
a=bundle.index('    // Init Desktop & Mobile Headers');b=bundle.index('    // Init Scrolltop',a)
bundle=bundle[:a]+'    if (!document.body.classList.contains("ui-native-shell")) {\n'+bundle[a:b]+'    }\n'+bundle[b:]
write('public/js/ui-legacy-widgets.js', '\n'.join(line.rstrip() for line in bundle.split('\n')))
# Dashboard: switch only content; preserve entire classic template including JS source exactly.
s=read('resources/views/dashboard_novo/index.blade.php')
if "@include('themes.refined.dashboard')" not in s:
 s=replace_once(s,"@section('content')", "@section('content')\n@if(config('ui_theme.active', false))\n    @include('themes.refined.dashboard')\n@else")
 pos=s.rindex('@endsection');s=s[:pos]+'@endif\n'+s[pos:]
write('resources/views/dashboard_novo/index.blade.php',s)
# Workbench is now a palette only in native shell but remains compatible with direct legacy extends.
manifest={'source_sha256':{p:hashlib.sha256(read(p).encode()).hexdigest() for p in ['resources/views/default/menu_lateral.blade.php','resources/views/default/menu_superior.blade.php','public/metronic/js/scripts.bundle.js']},'legacy_contracts_preserved':['action links','conditional modules','user/company permissions','alert modals','all page-specific scripts','dynamic grids','session widgets'],'native_layout': 'themes.refined.layout'}
# The committed baseline is an immutable contract. Rebuilding the native
# shell must NEVER silently rebaseline legacy hashes to its current inputs;
# otherwise a regression could be hidden by simply running the generator.
# The test explicitly normalizes only the approved Blade fixes.
contract_path = ROOT / 'tests/fixtures/ui/native-contract.json'
if not contract_path.is_file():
    raise RuntimeError('Missing immutable native UI baseline fixture')
existing_contract = json.loads(contract_path.read_text())
# Preserve the reviewed baseline from the committed fixture: a main->develop
# sync may legitimately change the classic menu, but cannot silently rewrite it.
baseline_commit = existing_contract.get('baseline_commit', '')
if not re.fullmatch(r'[a-f0-9]{40}', str(baseline_commit)):
    raise RuntimeError('Native UI baseline commit is invalid')
expected_sources = existing_contract.get('source_sha256', {})
if not isinstance(expected_sources, dict) or set(expected_sources) != set(manifest['source_sha256']):
    raise RuntimeError('Immutable legacy source list changed')
for path, sha in manifest['source_sha256'].items():
    if sha != expected_sources[path]:
        raise RuntimeError('Legacy source changed without reviewing baseline: ' + path)
manifest['baseline_commit'] = baseline_commit
# POS pages own their operational layout; only opt-in presentation is added.
for name in ['main', 'main2', 'main3', 'pdv_assincrono']:
 p='resources/views/frontBox/'+name+'.blade.php'
 s=read(p)
 if 'ui-pos-refined' in s:
  write(p, s.replace('ui-theme-refined--dark@endif', 'ui-theme-refined--dark @endif').replace('ui-density-compact@endif', 'ui-density-compact @endif'))
  continue
 before='</head>'
 extra="""@if(config('ui_theme.active', false))
    @php $uiResolvedTheme = app(\\App\\Services\\UiThemeService::class)->resolve(($tema ?? 1) == 2); @endphp
    @include('themes.refined.assets', ['uiResolvedTheme' => $uiResolvedTheme])
    <link rel="stylesheet" href="{{ asset('css/ui-pos.css') }}?v={{ filemtime(public_path('css/ui-pos.css')) }}">
@endif
</head>"""
 s=replace_once(s, before, extra)
 s=replace_once(s, 'page-loading pdv-minimal"', "page-loading pdv-minimal @if(config('ui_theme.active', false))ui-theme-refined ui-pos-refined @if($uiResolvedTheme['dark'] ?? false)ui-theme-refined--dark @endif @if(($uiResolvedTheme['density'] ?? '') === 'compact')ui-density-compact @endif @endif\"")
 write(p,s)
print('Generated native shell',len(layout),'and widgets compatibility',len(bundle))

