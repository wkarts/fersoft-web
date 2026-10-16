"""Controlled browser regression on real Laravel Blade and operational plugins.
No customer database, fiscal transmission or production login is used.
"""
from pathlib import Path
from urllib.parse import unquote, urlsplit
import http.server
import json
import os
import threading
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[2]
FIXTURES = ROOT / 'storage/app/ui-browser'


def prepare_existing_account(page):
    """The controlled account has already completed the application's intro tour.

    Production tour.js opens a full-screen backdrop on a first visit, so menu
    regression must exercise the ordinary returning-user session instead.
    """
    page.add_init_script("localStorage.setItem('tour-slym', 'true')")


class Handler(http.server.SimpleHTTPRequestHandler):
    def translate_path(self, path):
        relative = unquote(urlsplit(path).path).lstrip('/')
        if '..' in Path(relative).parts:
            return str(ROOT / 'public/404')
        return str(FIXTURES / relative[9:]) if relative.startswith('fixtures/') else str(ROOT / 'public' / relative)

    def log_message(self, *args):
        pass


def navigation_contract(page):
    """Capture the authorized menu tree, including child links and duplicate routes.

    The fixture uses the real Menu helper and a restricted in-memory company.
    Compare the rendered tree across layouts and presets rather than asserting a
    hard-coded copy of the production menu that would go stale as modules grow.
    """
    return page.locator('#ui-navigation .ui-nav-list').evaluate('''list =>
        Array.from(list.children).filter(item => item.matches('.menu-item')).map(item => {
            const link = Array.from(item.children).find(child => child.matches('a.menu-link'));
            return {
                label: link?.querySelector('.menu-text')?.textContent.trim() || '',
                href: link?.getAttribute('href') || '',
                children: Array.from(item.querySelectorAll('.menu-submenu a.menu-link'))
                    .map(child => [child.querySelector('.menu-text')?.textContent.trim() || '',
                        child.getAttribute('href') || ''])
            };
        })''')


def check_collapsed_navigation(page, persistent=False):
    original = navigation_contract(page)
    active = page.locator('#ui-navigation .ui-nav-list > .menu-item.menu-item-open > .menu-toggle').first
    active_id = active.get_attribute('aria-controls') if active.count() else None
    collapse = page.locator('[data-ui-collapse]')
    collapse.click()
    assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-nav-collapsed")')
    assert collapse.get_attribute('aria-expanded') == 'false'
    assert collapse.get_attribute('aria-label') == 'Expandir navegação'
    assert abs(page.locator('#ui-sidebar').bounding_box()['width'] - 80) <= 2, 'Collapsed rail must retain an 80px icon column.'
    assert page.locator('.ui-brand-symbol').is_visible(), 'The application symbol must remain reachable.'
    assert not page.locator('#ui-sidebar .brand-logo').is_visible()

    group = page.locator('#ui-navigation .ui-nav-list > .menu-item > .menu-toggle').filter(has_text='Cadastros').first
    assert group.get_attribute('title') == 'Cadastros', 'An icon-only group needs an accessible name.'
    group.click()
    submenu = page.locator('#' + group.get_attribute('aria-controls'))
    assert group.get_attribute('aria-expanded') == 'true' and submenu.is_visible()
    box = submenu.bounding_box()
    assert box['x'] >= 80 and box['x'] + box['width'] <= page.viewport_size['width'] + 2, 'Rail flyout is clipped.'
    assert submenu.locator('a[href="/produtos"]').is_visible(), 'The original product route disappeared from the flyout.'
    assert submenu.locator('a[href="/clientes"]').is_visible(), 'The original customer route disappeared from the flyout.'
    assert navigation_contract(page) == original, 'Collapsing changed the authorized menu tree.'
    page.keyboard.press('Escape')
    assert group.get_attribute('aria-expanded') == 'false' and not submenu.is_visible()
    assert group.evaluate('(e)=>document.activeElement===e'), 'Escape must return focus to the group icon.'

    group.press('ArrowRight')
    assert submenu.is_visible(), 'Keyboard users must be able to open the rail flyout.'
    page.mouse.click(page.viewport_size['width'] - 15, page.viewport_size['height'] - 15)
    assert not submenu.is_visible(), 'A click outside the rail should close the flyout.'
    assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-nav-collapsed")')
    if persistent:
        page.reload(wait_until='domcontentloaded')
        assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-nav-collapsed")'), 'Rail preference was not restored.'
        assert navigation_contract(page) == original
        collapse = page.locator('[data-ui-collapse]')
    collapse.click()
    assert not page.locator('body').evaluate('(e)=>e.classList.contains("ui-nav-collapsed")')
    assert collapse.get_attribute('aria-expanded') == 'true'
    if active_id:
        assert page.locator('#' + active_id).is_visible(), 'The current section must be restored after expanding.'
    assert navigation_contract(page) == original, 'Expanding changed authorized routes.'


def check_superior_navigation(page, resize=False):
    """The same permission-filtered menu becomes a 47px row below the header."""
    original = navigation_contract(page)
    sidebar = page.locator('#ui-sidebar')
    brand = sidebar.locator('.ui-brand .brand-logo')
    version = sidebar.locator('.ui-version a[href="/app-versions"]')
    assert sidebar.evaluate('(e)=>e.previousElementSibling?.matches(".ui-masthead")'), 'Top menu must follow the header.'
    assert sidebar.evaluate('(e)=>e.nextElementSibling?.id==="ui-session"'), 'Session context must follow the top menu.'
    assert abs(sidebar.bounding_box()['height'] - 47) <= 2, 'Top navigation must be 47px high.'
    assert abs(sidebar.bounding_box()['y'] - (page.locator('.ui-masthead').bounding_box()['y']
        + page.locator('.ui-masthead').bounding_box()['height'])) <= 2
    assert page.locator('#ui-navigation').evaluate('(e)=>getComputedStyle(e).overflowX') == 'auto'
    assert page.locator('.ui-nav-list').evaluate('(e)=>getComputedStyle(e).flexWrap') == 'nowrap'
    assert page.locator('#ui-navigation').evaluate('(e)=>e.nextElementSibling?.classList.contains("ui-version")'), \
        'Top navigation and keyboard order disagree.'
    assert brand.is_visible() and brand.get_attribute('href') == '/graficos', 'The application dashboard link is hidden in top layout.'
    assert version.is_visible() and version.inner_text().strip(), 'The original release notes link is hidden in top layout.'
    brand_box, nav_box, version_box = (item.bounding_box() for item in
        (brand, page.locator('#ui-navigation'), version))
    assert brand_box['x'] + brand_box['width'] <= nav_box['x'] + 2
    assert nav_box['x'] + nav_box['width'] <= version_box['x'] + 2
    assert version_box['x'] + version_box['width'] <= page.viewport_size['width'] + 2
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), 'Top row caused page overflow.'
    assert page.locator('#ui-navigation a[href="/logs"]').count() == 0, 'Unauthorized menu became visible.'

    first = page.locator('#ui-navigation .ui-nav-list > .menu-item > .menu-toggle').filter(has_text='Cadastros').first
    last = page.locator('#ui-navigation .ui-nav-list > .menu-item > .menu-toggle').last
    for group in (first, last):
        group.scroll_into_view_if_needed()
        group.click()
        submenu = page.locator('#' + group.get_attribute('aria-controls'))
        assert group.get_attribute('aria-expanded') == 'true' and submenu.is_visible()
        box = submenu.bounding_box()
        anchor = group.bounding_box()
        assert box['x'] >= 10 and box['x'] + box['width'] <= page.viewport_size['width'] + 2
        assert box['y'] >= anchor['y'] + anchor['height'] - 2, 'Top flyout is detached from its trigger.'
        assert submenu.locator('a[href]:not([href^="#"])').count() > 0, 'Top group lost its operational links.'
        group.press('ArrowLeft')
        assert not submenu.is_visible(), 'ArrowLeft must close the focused top group.'
        group.press('ArrowRight')
        assert submenu.is_visible(), 'ArrowRight must reopen the focused top group.'
        group.press('Escape')
        assert not submenu.is_visible(), 'Escape must close the top group.'
    assert navigation_contract(page) == original, 'Top layout changed the authorized menu tree.'

    # The real brand and version stay accessible even when company permissions
    # expose far more groups than fit the horizontal strip.
    crowded = page.evaluate('''() => {
        const navigation = document.querySelector('#ui-navigation');
        const list = navigation.querySelector('.ui-nav-list');
        const source = list.querySelector(':scope > .menu-item');
        while (list.querySelectorAll(':scope > .menu-item').length < 36) {
            const copy = source.cloneNode(true);
            copy.classList.add('ui-top-density-fixture');
            list.appendChild(copy);
        }
        navigation.scrollLeft = navigation.scrollWidth;
        return {overflow: navigation.scrollWidth > navigation.clientWidth,
            atEnd: navigation.scrollLeft > 0};
    }''')
    assert crowded['overflow'] and crowded['atEnd'], 'The 36-group strip lost horizontal scrolling.'
    assert brand.is_visible() and version.is_visible(), 'Pinned existing links disappeared while the groups scrolled.'
    assert version.bounding_box()['x'] + version.bounding_box()['width'] <= page.viewport_size['width'] + 2
    page.evaluate('''() => {
        document.querySelectorAll('.ui-top-density-fixture').forEach(item => item.remove());
        document.querySelector('#ui-navigation').scrollLeft = 0;
    }''')
    assert navigation_contract(page) == original

    # SUPER and other broad roles can place about 29 real routes under one
    # group. The flyout needs its own vertical scrolling inside the viewport.
    first.scroll_into_view_if_needed()
    page.evaluate('''() => {
        const list = document.querySelector('#ui-navigation .ui-nav-list > .menu-item .menu-subnav');
        const source = list.querySelector(':scope > .menu-item:not(.menu-item-parent)');
        while (list.querySelectorAll(':scope > .menu-item:not(.menu-item-parent)').length < 29) {
            const copy = source.cloneNode(true);
            copy.classList.add('ui-top-long-flyout-fixture');
            list.appendChild(copy);
        }
    }''')
    first.click()
    long_flyout = page.locator('#' + first.get_attribute('aria-controls'))
    assert long_flyout.is_visible()
    flyout_state = long_flyout.evaluate('''e => {
        e.scrollTop = e.scrollHeight;
        const box = e.getBoundingClientRect();
        return {scrolls: e.scrollHeight > e.clientHeight && e.scrollTop > 0,
            bottom: box.bottom, top: box.top};
    }''')
    assert flyout_state['scrolls'], 'The long SUPER-style flyout cannot scroll through its routes.'
    assert flyout_state['top'] >= 0 and flyout_state['bottom'] <= page.viewport_size['height'] + 2, \
        'The long flyout extends beyond the viewport.'
    first.press('Escape')
    page.evaluate('''() => document.querySelectorAll('.ui-top-long-flyout-fixture').forEach(item => item.remove())''')
    assert navigation_contract(page) == original

    # A long operational view must keep the 47px row usable without covering
    # the company, unit and user controls in the header while scrolling.
    page.evaluate('''() => {
        const spacer = document.createElement('div');
        spacer.id = 'ui-top-scroll-test';
        spacer.style.height = '1800px';
        document.querySelector('.ui-page').appendChild(spacer);
        window.scrollTo(0, 600);
    }''')
    page.wait_for_function('window.scrollY >= 400')
    positions = page.evaluate('''() => ({
        header: document.querySelector('.ui-masthead').getBoundingClientRect().toJSON(),
        menu: document.querySelector('#ui-sidebar').getBoundingClientRect().toJSON()
    })''')
    header, menu = positions['header'], positions['menu']
    assert menu['top'] >= -2 and menu['bottom'] <= page.viewport_size['height'], 'Top menu disappeared while scrolling.'
    assert (header['bottom'] <= menu['top'] + 2 or header['top'] >= menu['bottom'] - 2), \
        'Top menu overlaps the header after scrolling.'
    first.click()
    submenu = page.locator('#' + first.get_attribute('aria-controls'))
    assert submenu.is_visible() and submenu.bounding_box()['y'] >= menu['bottom'] - 2, \
        'The top flyout is inaccessible after scrolling.'
    first.press('Escape')
    page.evaluate('''() => {
        document.querySelector('#ui-top-scroll-test').remove();
        window.scrollTo(0, 0);
    }''')

    if resize:
        page.set_viewport_size({'width': 768, 'height': 1024})
        assert sidebar.evaluate('(e)=>e.parentElement?.matches(".ui-app")'), 'Top menu did not return to the mobile drawer.'
        assert sidebar.locator('.ui-brand').evaluate('(e)=>e.nextElementSibling?.classList.contains("ui-version")'), \
            'The drawer did not restore its original keyboard order.'
        assert sidebar.evaluate('(e)=>e.inert'), 'Closed mobile drawer must not intercept keyboard focus.'
        page.locator('[data-ui-menu-open]').click()
        assert sidebar.get_attribute('aria-modal') == 'true'
        assert brand.is_visible() and version.is_visible(), 'Existing dashboard or release links disappeared from the mobile drawer.'
        page.keyboard.press('Escape')
        assert sidebar.evaluate('(e)=>e.inert')
        page.set_viewport_size({'width': 1920, 'height': 1032})
        assert sidebar.evaluate('(e)=>e.previousElementSibling?.matches(".ui-masthead")')
        assert abs(sidebar.bounding_box()['height'] - 47) <= 2
        assert navigation_contract(page) == original
        assert page.evaluate('document.documentElement.scrollWidth <= innerWidth + 2')


def check_page(page, theme, layout, density, width):
    assert page.locator('h1').first.inner_text() == 'Dashboard Analítico Executivo'
    assert '6.479.876,12' in page.locator('.ui-metrics').inner_text()
    assert page.locator('.ui-chart-empty').count() == 2
    assert page.locator('.ui-chart-empty').first.evaluate('(e)=>e.getBoundingClientRect().height <= 205'), 'Empty charts do not match the proposal height.'
    assert page.locator('.ui-metric').first.evaluate('(e)=>e.getBoundingClientRect().height <= 128'), 'Metric cards exceed the proposal height.'
    assert page.locator('#ui-navigation a[href="/logs"]').count() == 0, 'Unauthorized menu was exposed.'
    assert page.locator('#ui-navigation a[href="/produtos"]').count() == 1
    assert page.locator('#ui-navigation a[href="/clientes"]').count() == 1
    assert page.locator('#ui-navigation a[href="/funcionarios"]').count() >= 1
    assert len(navigation_contract(page)) >= 8, 'The permission-filtered menu lost groups.'
    for selector in ['a[href="/login/logoff"]', '#set-location-top', '#btn-whatsapp-global', '#dataHoraClock']:
        assert page.locator(selector).count() == 1, 'Missing/duplicated operational control: ' + selector
    assert page.locator('.ui-finance-link').count() == 4
    assert page.locator('.ui-header-theme').count() == 1, 'Theme action is missing or duplicated.'
    assert page.locator('.ui-header-pdv a[href="/frenteCaixa"]').count() == 1, 'The existing PDV action is missing.'
    assert page.locator('.ui-masthead-tools').count() == 0, 'Unrequested search/theme shortcuts are visible.'
    assert page.locator('[data-ui-command-open]').count() == 0, 'Unrequested module search is visible.'
    assert page.locator('.ui-sidebar-footer, [data-ui-support-open], #ui-support-dialog').count() == 0, 'An unrequested support footer or dialog was added.'
    assert page.evaluate('window.hubSettings && window.hubSettings.hideMessageBubble === false'), 'The original support launcher must remain available.'
    assert page.locator('.ui-sidebar-footer a[href*="/ui/temas"]').count() == 0, 'Theme choice must stay in the header.'
    sidebar_color = page.locator('#ui-sidebar').evaluate('(e)=>getComputedStyle(e).backgroundColor')
    canvas_color = page.locator('body').evaluate('(e)=>getComputedStyle(e).backgroundColor')
    body_text_color = page.locator('body').evaluate('(e)=>getComputedStyle(e).color')
    operator_color = page.locator('.ui-actions .user-tour .text-info').first.evaluate('(e)=>getComputedStyle(e).color')
    assert operator_color == body_text_color, 'The operator name must use the proposal text color.'
    session_info = page.locator('.ui-session .text-info').first
    if session_info.count():
        assert session_info.evaluate('(e)=>getComputedStyle(e).color') == body_text_color, 'Session details must use the proposal text color.'
    tokens = page.locator('body').evaluate('''e => {
        const s = getComputedStyle(e);
        return {accent: s.getPropertyValue('--ui-accent').trim().toUpperCase(),
            sidebar: s.getPropertyValue('--ui-aside').trim().toUpperCase(),
            background: s.getPropertyValue('--ui-bg').trim().toUpperCase()};
    }''')
    assert page.locator('body').get_attribute('data-ui-theme') == theme
    if theme == 'fer-light':
        assert tokens == {'accent':'#1F9866','sidebar':'#FFFFFF','background':'#F5F7F7'}
        assert sidebar_color == 'rgb(255, 255, 255)', 'The approved default uses a light sidebar.'
        assert canvas_color == 'rgb(245, 247, 247)', 'The approved default uses the proposal canvas.'
    if theme == 'fer-slate':
        assert tokens == {'accent':'#1F9866','sidebar':'#1D2639','background':'#F5F7F7'}
        if layout == 'lateral' or width < 992:
            assert sidebar_color == 'rgb(29, 38, 57)', 'The optional slate sidebar uses the approved dark color.'
        else:
            assert sidebar_color == 'rgb(255, 255, 255)', 'The 47px top strip uses the light workspace surface.'
        assert canvas_color == 'rgb(245, 247, 247)', 'The slate variant keeps the workspace light.'
    if theme == 'fer-night':
        assert tokens == {'accent':'#62D3AB','sidebar':'#172D35','background':'#11252C'}
        assert canvas_color == 'rgb(17, 37, 44)'
        assert sidebar_color == ('rgb(23, 45, 53)' if layout == 'lateral' or width < 992 else 'rgb(28, 51, 59)')
    if theme == 'fer-mist':
        assert tokens == {'accent':'#167D5B','sidebar':'#E8F2ED','background':'#F2F7F4'}
        assert canvas_color == 'rgb(242, 247, 244)'
        assert sidebar_color == ('rgb(232, 242, 237)' if layout == 'lateral' or width < 992 else 'rgb(255, 255, 255)')
    if theme == 'fer-graphite':
        assert tokens == {'accent':'#75D9B6','sidebar':'#111A25','background':'#171C25'}
        assert canvas_color == 'rgb(23, 28, 37)'
        assert sidebar_color == ('rgb(17, 26, 37)' if layout == 'lateral' or width < 992 else 'rgb(34, 43, 54)')
    assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth + 2'), 'Horizontal page overflow'
    assert page.locator('body').evaluate('(e)=>getComputedStyle(e).fontFamily.includes("Poppins")'), 'The approved Poppins typography is missing.'
    font_state = page.evaluate('''async()=>{
        await document.fonts.ready;
        try {
            const loaded = await document.fonts.load("400 13px Poppins", "Amostra");
            return {loaded: loaded.length, check: document.fonts.check("400 13px Poppins"), faces: [...document.fonts].map(face => ({family: face.family, weight: face.weight, status: face.status}))};
        } catch (error) {
            return {error: String(error), faces: [...document.fonts].map(face => ({family: face.family, weight: face.weight, status: face.status}))};
        }
    }''')
    assert font_state.get('loaded', 0) > 0 and font_state.get('check'), f'The local Poppins font did not load: {font_state}'
    page.keyboard.press('Tab')
    focus_style = page.locator(':focus').evaluate('(e)=>{const s=getComputedStyle(e);return {outline:s.outlineStyle,boxShadow:s.boxShadow,visibleSkip:e.matches(".ui-skip")&&e.getBoundingClientRect().top>=0}}')
    assert focus_style['outline'] != 'none' or focus_style['boxShadow'] != 'none' or focus_style['visibleSkip'], f'Keyboard focus must remain visible: {focus_style}'
    page.locator(':focus').evaluate('(e)=>e.blur()')
    metric_rows = page.locator('.ui-metric').evaluate_all('(items)=>items.map(e=>e.getBoundingClientRect().top)')
    assert abs(metric_rows[0] - metric_rows[1]) < 5, 'Metric cards must reflow into a usable grid.'
    if width < 1300:
        assert metric_rows[2] > metric_rows[0] + 5, 'Tablet and mobile metrics must wrap instead of shrinking the desktop grid.'
    else:
        assert max(metric_rows) - min(metric_rows) < 5, 'Desktop metrics must retain the four-column proposal grid.'
    assert page.locator('body').get_attribute('data-ui-layout') == layout
    if width >= 992 and layout == 'lateral':
        header = page.locator('.ui-masthead').bounding_box()
        brand = page.locator('.ui-brand').bounding_box()
        session = page.locator('.ui-session').bounding_box()
        version = page.locator('.ui-version').bounding_box()
        assert abs((header['y'] + header['height']) - (brand['y'] + brand['height'])) <= 2, \
            'The main header and sidebar brand separator do not align.'
        assert abs((session['y'] + session['height']) - (version['y'] + version['height'])) <= 2, \
            'The session strip and sidebar version separator do not align.'
    assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-theme-refined--dark")') == (theme in ('fer-night', 'fer-graphite'))
    assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-density-compact")') == (density == 'compact')
    if width < 992:
        page.locator('[data-ui-menu-open]').click()
        assert page.locator('#kt_wrapper').evaluate('(e)=>e.inert')
        assert page.locator('#ui-sidebar').get_attribute('aria-modal') == 'true'
        page.keyboard.press('Escape')
        assert not page.locator('#kt_wrapper').evaluate('(e)=>e.inert')
        assert page.locator('[data-ui-menu-open]').get_attribute('aria-expanded') == 'false'
    else:
        link = page.locator('#ui-navigation .menu-toggle').filter(has_text='Cadastros').first
        link.click()
        assert link.get_attribute('aria-expanded') == 'true'
        link.click()
        assert link.get_attribute('aria-expanded') == 'false'
        if layout == 'lateral':
            unit = page.locator('.ui-header-unit')
            theme_action = page.locator('.ui-header-theme')
            if unit.count() and theme_action.count():
                assert unit.bounding_box()['x'] < theme_action.bounding_box()['x'], 'Unit selection must precede theme actions in the header.'
            check_collapsed_navigation(page, persistent=(theme == 'fer-light' and density == 'comfortable' and width == 1920))
        else:
            check_superior_navigation(page, resize=(theme == 'fer-light' and density == 'comfortable' and width == 1920))
    page.locator('[data-target="#fixture-modal"]').click()
    page.locator('#fixture-modal').wait_for(state='visible')
    assert page.locator('#fixture-name').input_value() == 'Preservado'
    page.locator('#fixture-name').fill('Alteração de teste mantida')
    page.locator('#fixture-modal [data-dismiss="modal"]').click()
    page.locator('#fixture-modal').wait_for(state='hidden')
    page.locator('[data-target="#fixture-modal"]').click()
    assert page.locator('#fixture-name').input_value() == 'Alteração de teste mantida'
    page.locator('#fixture-modal [data-dismiss="modal"]').click()
    page.locator('#fixture-modal').wait_for(state='hidden')
    select = page.locator('#fixture-select')
    assert select.evaluate('(e)=>jQuery(e).hasClass("select2-hidden-accessible")')
    select.evaluate('(e)=>jQuery(e).select2("open")')
    page.get_by_role('option', name='Outro item', exact=True).click()
    assert select.input_value() == '2'
    table = page.locator('#fixture-grid')
    assert table.evaluate('(e)=>!!jQuery(e).closest(".datatable").data("KTDatatable")')
    assert 'Produto de teste' in table.inner_text()
    table.evaluate('(e)=>jQuery(e).closest(".datatable").data("KTDatatable").search("inexistente")')
    page.wait_for_timeout(250)
    table.evaluate('(e)=>jQuery(e).closest(".datatable").data("KTDatatable").search("")')
    page.wait_for_timeout(250)
    assert 'Produto de teste' in table.inner_text(), 'Datagrid did not recover after a filter.'
    page.evaluate('document.activeElement?.blur()')
    page.evaluate('window.scrollTo(0,0)')
    return navigation_contract(page)


def check_whatsapp_dialog(page, width):
    """The existing header helper must open a full-width, themed dialog.

    Empty-number validation is local; this test never submits a WhatsApp request.
    """
    dialog = page.locator('#modalWhatsAppGlobal')
    assert dialog.evaluate('(e)=>e.parentElement===document.body'), 'WhatsApp dialog still inherits header layout and stacking.'
    assert page.locator('#modalConfirmacaoGlobal').evaluate('(e)=>e.parentElement===document.body')
    assert page.locator('#modalMensagemGlobal').evaluate('(e)=>e.parentElement===document.body')
    page.locator('#btn-whatsapp-global').click()
    dialog.wait_for(state='visible')
    assert dialog.evaluate('''e => getComputedStyle(e.querySelector('.modal-header')).backgroundColor ===
        getComputedStyle(document.body).getPropertyValue('--ui-surface').trim().replace(/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i,
            (_,r,g,b) => `rgb(${parseInt(r,16)}, ${parseInt(g,16)}, ${parseInt(b,16)})`)'''), \
        'WhatsApp header ignores the selected surface.'
    number = dialog.locator('#wh_number_global').bounding_box()
    caption = dialog.locator('#wh_caption_global').bounding_box()
    message = dialog.locator('#wh_text_msg_global').bounding_box()
    assert number['width'] >= (300 if width >= 767 else 270), 'The header constrains the phone field.'
    assert abs(number['width'] - caption['width']) <= 2, 'The form columns have unequal widths.'
    assert message['width'] >= number['width'], 'The message field does not use the dialog width.'
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), 'WhatsApp dialog causes horizontal overflow.'
    assert dialog.locator('.ui-whatsapp-footer-hint').evaluate('(e)=>parseFloat(getComputedStyle(e).fontSize) >= 11')
    assert dialog.locator('label[for="wh_number_global"]').count() == 1
    assert dialog.locator('label[for="wh_files_input_global"]').count() == 1
    dialog.locator('#wh_send_btn_global').click()
    error_dialog = page.locator('#modalMensagemGlobal')
    error_dialog.wait_for(state='visible')
    assert 'Informe o número' in error_dialog.inner_text()
    error_dialog.locator('[data-dismiss="modal"]').first.click()
    error_dialog.wait_for(state='hidden')
    dialog.locator('[data-dismiss="modal"]').first.click()
    dialog.wait_for(state='hidden')


def check_employee_page(page, kind, width):
    """Exercise actual operational Blade, with safe fixture values only."""
    assert page.locator('body.ui-native-shell').count() == 1
    assert page.locator('#ui-navigation a[href="/funcionarios"]').count() >= 1
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), 'Operational page overflows viewport.'
    assert page.locator('body').evaluate('(e)=>getComputedStyle(e).fontFamily.includes("Poppins")')
    if width < 992:
        page.locator('[data-ui-menu-open]').click()
        assert page.locator('#ui-sidebar').get_attribute('aria-modal') == 'true'
        page.keyboard.press('Escape')
    if kind == 'list':
        assert page.locator('h1').first.inner_text() == 'Funcionários'
        assert page.locator('table.ui-people-table tbody tr').count() == 1
        assert 'Funcionário de teste' in page.locator('table.ui-people-table').inner_text()
        assert page.locator('.ui-people-actions a[href="/funcionarios/new"]').count() == 1
        assert page.locator('.ui-people-actions a[href="/funcionarios/comissao"]').count() == 1
        page.locator('[data-people-tab="grid"]').click()
        assert page.locator('#ui-people-grid').is_visible()
        assert 'Funcionário de teste' in page.locator('#ui-people-grid').inner_text()
        page.locator('[data-people-tab="table"]').click()
        assert page.locator('#ui-people-table').is_visible()
        page.locator('[data-target="#modal_import_excel"]').click()
        page.locator('#modal_import_excel').wait_for(state='visible')
        assert page.locator('#modal_import_excel form').get_attribute('action') == '/funcionarios/importExcel'
        assert page.locator('#modal_import_excel input[name="file"]').get_attribute('type') == 'file'
        assert page.locator('#modal_import_excel a[href="/funcionarios/downloadLayout"]').count() == 1
        page.locator('#modal_import_excel [data-dismiss="modal"]').first.click()
        page.locator('#modal_import_excel').wait_for(state='hidden')
    else:
        assert page.get_by_text('Novo Colaborador').count() >= 1
        form = page.locator('form[action="/funcionarios/save"]').first
        assert form.get_attribute('method').lower() == 'post'
        for name in ['nome', 'cpf', 'rg', 'data_nascimento', 'data_admissao',
                     'status_funcionario', 'cep', 'rua', 'numero', 'telefone',
                     'funcao_id', 'filial_id', 'usuario_id', 'salario', 'cnh']:
            assert form.locator(f'[name="{name}"]').count() >= 1, 'Missing employee field ' + name
        assert form.locator('[name="nome"]').get_attribute('required') is not None
        form.locator('[name="nome"]').fill('Nome de teste não enviado')
        assert form.locator('[name="nome"]').input_value() == 'Nome de teste não enviado'
        assert not form.evaluate('(e)=>e.checkValidity()'), 'Required CPF validation was lost.'
        assert form.locator('[name="funcao_id"]').evaluate('(e)=>jQuery(e).hasClass("select2-hidden-accessible")')
        page.locator('[data-target="#modal_funcao"]').click()
        page.locator('#modal_funcao').wait_for(state='visible')
        assert page.locator('#nome_funcao_quick').count() == 1
        page.locator('#modal_funcao [data-dismiss="modal"]').first.click()
        page.locator('#modal_funcao').wait_for(state='hidden')
        assert page.locator('a[href="/funcionarios"]').count() >= 1


def check_critical_flow(page, kind, width):
    """Real CT-e and PDV templates, without committing or transmitting data."""
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth + 2'), kind + ' overflows viewport.'
    assert page.locator('body').evaluate('(e)=>getComputedStyle(e).fontFamily.includes("Poppins")')
    assert page.locator('body').evaluate('(e)=>getComputedStyle(e).backgroundColor') == 'rgb(245, 247, 247)'
    if kind == 'cte-register':
        assert page.locator('body.ui-native-shell .cte-page--register').count() == 1
        assert 'EMISSÃO DE CTe' in page.locator('h1.cte-page__title').inner_text()
        assert page.locator('form[action="/cte/importarXml"] input[name="file"]').count() == 1
        for name in ['natureza', 'cliente', 'tomador', 'valor_carga', 'quantidade_carga']:
            assert page.locator(f'[name="{name}"]').count() >= 1, 'CT-e field missing: ' + name
        assert page.locator('#kt_select2_1').evaluate('(e)=>jQuery(e).hasClass("select2-hidden-accessible")')
        page.locator('button[onclick="novoRemetente()"]').first.click()
        page.locator('#modal-cliente').wait_for(state='visible')
        assert page.locator('#modal-cliente #razao_social2').count() == 1
        assert page.locator('#remetente_destinatario').input_value() == 'remetente'
        page.locator('#modal-cliente [data-dismiss="modal"]').first.click()
        page.locator('#modal-cliente').wait_for(state='hidden')
        assert page.locator('#finalizar[onclick="salvarCTe()"]').count() == 1
    else:
        assert page.locator('body.ui-pos-refined .ui-pos-frame').count() == 1
        assert page.locator('a[href="/frenteCaixa/list"]').count() >= 1
        assert page.locator('#produto-search').count() == 1
        assert page.locator('#tipo-pagamento option[value="01"]').count() == 1
        assert page.locator('#finalizar-venda').is_disabled(), 'Empty sale must not be finalized.'
        page.locator('.pdv-top-actions [data-toggle="dropdown"]').click()
        page.locator('.pdv-top-actions [data-target="#modal2"]').click()
        page.locator('#modal2').wait_for(state='visible')
        assert page.locator('#modal2 .modal-content').count() == 1
        page.locator('#modal2 [data-dismiss="modal"]').first.click()
        page.locator('#modal2').wait_for(state='hidden')


def main():
    layout_source = (ROOT / 'resources/views/themes/refined/layout.blade.php').read_text()
    support_source = (ROOT / 'resources/views/layouts/support.blade.php').read_text()
    assert "@include('layouts.support')" in layout_source, 'The existing support integration was removed.'
    assert 'ui-sidebar-footer' not in layout_source and 'data-ui-support-open' not in layout_source, 'A new support footer must not be added.'
    assert '"hideMessageBubble": false' in support_source, 'The original support launcher must remain visible.'
    server = http.server.ThreadingHTTPServer(('127.0.0.1', 8765), Handler)
    threading.Thread(target=server.serve_forever, daemon=True).start()
    report = []
    try:
        with sync_playwright() as playwright:
            options = {'executable_path': os.environ['UI_CHROMIUM']} if os.getenv('UI_CHROMIUM') else {}
            browser = playwright.chromium.launch(headless=True, **options)
            expected_menu = None
            for theme in ['fer-light', 'fer-slate', 'fer-night', 'fer-mist', 'fer-graphite']:
                for layout in ['lateral', 'superior']:
                    for density in ['comfortable', 'compact']:
                        for width, height in [(1920, 1032), (1366, 768), (768, 1024), (390, 844)]:
                            filename = f'{theme}-{layout}-{density}.html'
                            case = {'fixture': filename, 'viewport': f'{width}x{height}', 'status': 'running'}
                            errors = []
                            page = browser.new_page(viewport={'width': width, 'height': height}, reduced_motion='reduce')
                            prepare_existing_account(page)
                            page.set_default_timeout(12000)
                            page.on('pageerror', lambda error: errors.append(str(error)))
                            page.route('https://**/*', lambda route: route.abort())
                            try:
                                page.goto(f'http://127.0.0.1:8765/fixtures/{filename}', wait_until='domcontentloaded')
                                page.wait_for_timeout(300)
                                rendered_menu = check_page(page, theme, layout, density, width)
                                if theme in ('fer-light', 'fer-night') and layout == 'lateral' \
                                        and density == 'comfortable' and width in (1920, 390):
                                    check_whatsapp_dialog(page, width)
                                if expected_menu is None:
                                    expected_menu = rendered_menu
                                else:
                                    assert rendered_menu == expected_menu, 'Theme, layout or viewport changed the authorized groups, names or routes.'
                                assert not errors, 'Unexpected JavaScript exception: ' + '; '.join(errors)
                                if density == 'comfortable':
                                    page.screenshot(path=str(FIXTURES / f'{theme}-{layout}-{width}.png'), full_page=True)
                                case['status'] = 'passed'
                            except Exception as error:
                                case['status'] = 'failed'
                                case['error'] = str(error)
                                page.screenshot(path=str(FIXTURES / f'FAILED-{theme}-{layout}-{density}-{width}.png'), full_page=True)
                                raise
                            finally:
                                case['javascript_errors'] = errors
                                report.append(case)
                                page.close()
                            print(filename, f'{width}x{height}', 'passed', flush=True)
            for kind in ['list', 'register']:
                filename = f'operational-funcionarios-{kind}.html'
                for width, height in [(1920, 1032), (1366, 768), (768, 1024), (390, 844)]:
                    case = {'fixture': filename, 'viewport': f'{width}x{height}', 'status': 'running'}
                    errors = []
                    page = browser.new_page(viewport={'width': width, 'height': height}, reduced_motion='reduce')
                    prepare_existing_account(page)
                    page.set_default_timeout(12000)
                    page.on('pageerror', lambda error: errors.append(str(error)))
                    page.route('https://**/*', lambda route: route.abort())
                    try:
                        page.goto(f'http://127.0.0.1:8765/fixtures/{filename}', wait_until='domcontentloaded')
                        page.wait_for_timeout(300)
                        check_employee_page(page, kind, width)
                        assert not errors, 'Unexpected JavaScript exception: ' + '; '.join(errors)
                        page.screenshot(path=str(FIXTURES / f'operational-funcionarios-{kind}-{width}.png'), full_page=True)
                        case['status'] = 'passed'
                    except Exception as error:
                        case['status'] = 'failed'
                        case['error'] = str(error)
                        page.screenshot(path=str(FIXTURES / f'FAILED-operational-funcionarios-{kind}-{width}.png'), full_page=True)
                        raise
                    finally:
                        case['javascript_errors'] = errors
                        report.append(case)
                        page.close()
                    print(filename, f'{width}x{height}', 'passed', flush=True)
            for kind in ['cte-register', 'pdv']:
                filename = f'operational-{kind}.html'
                for width, height in [(1920, 1032), (1366, 768), (768, 1024), (390, 844)]:
                    case = {'fixture': filename, 'viewport': f'{width}x{height}', 'status': 'running'}
                    errors = []
                    page = browser.new_page(viewport={'width': width, 'height': height}, reduced_motion='reduce')
                    prepare_existing_account(page)
                    page.set_default_timeout(12000)
                    page.on('pageerror', lambda error: errors.append(str(error)))
                    page.route('https://**/*', lambda route: route.abort())
                    if kind == 'pdv':
                        page.route('**/aberturaCaixa/verificaHoje', lambda route: route.fulfill(
                            status=200, content_type='application/json', body='0'))
                    try:
                        page.goto(f'http://127.0.0.1:8765/fixtures/{filename}', wait_until='domcontentloaded')
                        page.wait_for_timeout(350)
                        check_critical_flow(page, kind, width)
                        assert not errors, 'Unexpected JavaScript exception: ' + '; '.join(errors)
                        page.screenshot(path=str(FIXTURES / f'operational-{kind}-{width}.png'), full_page=True)
                        case['status'] = 'passed'
                    except Exception as error:
                        case['status'] = 'failed'
                        case['error'] = str(error)
                        page.screenshot(path=str(FIXTURES / f'FAILED-operational-{kind}-{width}.png'), full_page=True)
                        raise
                    finally:
                        case['javascript_errors'] = errors
                        report.append(case)
                        page.close()
                    print(filename, f'{width}x{height}', 'passed', flush=True)
            browser.close()
    finally:
        server.shutdown()
        (FIXTURES / 'browser-results.json').write_text(json.dumps(report, ensure_ascii=False, indent=2))
    assert len(report) == 96 and all(row['status'] == 'passed' for row in report)
    print('96 real Blade/browser scenarios passed with no JavaScript exceptions.')


if __name__ == '__main__':
    main()

