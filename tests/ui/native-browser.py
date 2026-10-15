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


class Handler(http.server.SimpleHTTPRequestHandler):
    def translate_path(self, path):
        relative = unquote(urlsplit(path).path).lstrip('/')
        if '..' in Path(relative).parts:
            return str(ROOT / 'public/404')
        return str(FIXTURES / relative[9:]) if relative.startswith('fixtures/') else str(ROOT / 'public' / relative)

    def log_message(self, *args):
        pass


def check_page(page, theme, layout, density, width):
    assert page.locator('h1').first.inner_text() == 'Dashboard Analítico Executivo'
    assert '6.479.876,12' in page.locator('.ui-metrics').inner_text()
    assert page.locator('.ui-chart-empty').count() == 2
    assert page.locator('#ui-navigation a[href="/logs"]').count() == 0, 'Unauthorized menu was exposed.'
    for selector in ['a[href="/login/logoff"]', '#set-location-top', '#btn-whatsapp-global', '#dataHoraClock']:
        assert page.locator(selector).count() == 1, 'Missing/duplicated operational control: ' + selector
    assert page.locator('.ui-finance-link').count() == 4
    assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth + 2'), 'Horizontal page overflow'
    assert page.locator('body').get_attribute('data-ui-layout') == layout
    assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-theme-refined--dark")') == (theme == 'fer-night')
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
            page.locator('[data-ui-collapse]').click()
            assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-nav-collapsed")')
            page.locator('[data-ui-collapse]').click()
        else:
            last_group = page.locator('#ui-navigation .ui-nav-list > .menu-item > .menu-toggle').last
            last_group.click()
            submenu_id = last_group.get_attribute('aria-controls')
            assert page.locator('#' + submenu_id).evaluate('(e)=>{const r=e.getBoundingClientRect();return r.left>=0 && r.right<=innerWidth+1}')
            page.keyboard.press('Escape')
    page.keyboard.press('Control+k')
    assert page.locator('#ui-command-palette').is_visible()
    page.locator('#ui-command-search').fill('Financeiro')
    assert page.locator('.ui-command__result:visible').count() > 0
    page.keyboard.press('Escape')
    assert not page.locator('#ui-command-palette').is_visible()
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


def main():
    server = http.server.ThreadingHTTPServer(('127.0.0.1', 8765), Handler)
    threading.Thread(target=server.serve_forever, daemon=True).start()
    report = []
    try:
        with sync_playwright() as playwright:
            options = {'executable_path': os.environ['UI_CHROMIUM']} if os.getenv('UI_CHROMIUM') else {}
            browser = playwright.chromium.launch(headless=True, **options)
            for theme in ['fer-light', 'fer-slate', 'fer-night']:
                for layout in ['lateral', 'superior']:
                    for density in ['comfortable', 'compact']:
                        for width, height in [(1440, 1000), (390, 844)]:
                            filename = f'{theme}-{layout}-{density}.html'
                            case = {'fixture': filename, 'viewport': width, 'status': 'running'}
                            errors = []
                            page = browser.new_page(viewport={'width': width, 'height': height}, reduced_motion='reduce')
                            page.set_default_timeout(12000)
                            page.on('pageerror', lambda error: errors.append(str(error)))
                            page.route('https://**/*', lambda route: route.abort())
                            try:
                                page.goto(f'http://127.0.0.1:8765/fixtures/{filename}', wait_until='domcontentloaded')
                                page.wait_for_timeout(300)
                                check_page(page, theme, layout, density, width)
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
                            print(filename, width, 'passed', flush=True)
            browser.close()
    finally:
        server.shutdown()
        (FIXTURES / 'browser-results.json').write_text(json.dumps(report, ensure_ascii=False, indent=2))
    assert len(report) == 24 and all(row['status'] == 'passed' for row in report)
    print('24 real Blade/browser scenarios passed with no JavaScript exceptions.')


if __name__ == '__main__':
    main()
