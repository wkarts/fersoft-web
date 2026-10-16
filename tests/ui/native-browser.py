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


def check_page(page, theme, layout, density, width):
    assert page.locator('h1').first.inner_text() == 'Dashboard Analítico Executivo'
    assert '6.479.876,12' in page.locator('.ui-metrics').inner_text()
    assert page.locator('.ui-chart-empty').count() == 2
    assert page.locator('.ui-chart-empty').first.evaluate('(e)=>e.getBoundingClientRect().height <= 205'), 'Empty charts do not match the proposal height.'
    assert page.locator('.ui-metric').first.evaluate('(e)=>e.getBoundingClientRect().height <= 128'), 'Metric cards exceed the proposal height.'
    assert page.locator('#ui-navigation a[href="/logs"]').count() == 0, 'Unauthorized menu was exposed.'
    for selector in ['a[href="/login/logoff"]', '#set-location-top', '#btn-whatsapp-global', '#dataHoraClock']:
        assert page.locator(selector).count() == 1, 'Missing/duplicated operational control: ' + selector
    assert page.locator('.ui-finance-link').count() == 4
    assert page.locator('.ui-header-theme').count() == 1, 'Theme action is missing or duplicated.'
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
    if theme == 'fer-light':
        assert sidebar_color == 'rgb(255, 255, 255)', 'The approved default uses a light sidebar.'
        assert canvas_color == 'rgb(245, 247, 247)', 'The approved default uses the proposal canvas.'
    if theme == 'fer-slate':
        assert sidebar_color == 'rgb(29, 38, 57)', 'The optional slate variant uses the proposal dark-sidebar color.'
        assert canvas_color == 'rgb(245, 247, 247)', 'The slate variant keeps the workspace light.'
    assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth + 2'), 'Horizontal page overflow'
    assert page.locator('body').evaluate('(e)=>getComputedStyle(e).fontFamily.includes("Poppins")'), 'The approved Poppins typography is missing.'
    font_state = page.evaluate('''async()=>{
        await document.fonts.ready;
        try {
            const loaded = await document.fonts.load("400 13px Poppins", "FERSOFT");
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
            unit = page.locator('.ui-header-unit')
            theme_action = page.locator('.ui-header-theme')
            if unit.count() and theme_action.count():
                assert unit.bounding_box()['x'] < theme_action.bounding_box()['x'], 'Unit selection must precede theme actions in the header.'
            page.locator('[data-ui-collapse]').click()
            assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-nav-collapsed")')
            page.locator('[data-ui-collapse]').click()
        else:
            last_group = page.locator('#ui-navigation .ui-nav-list > .menu-item > .menu-toggle').last
            last_group.click()
            submenu_id = last_group.get_attribute('aria-controls')
            assert page.locator('#' + submenu_id).evaluate('(e)=>{const r=e.getBoundingClientRect();return r.left>=0 && r.right<=innerWidth+1}')
            page.keyboard.press('Escape')
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
        assert page.locator('a[href="/funcionarios/new"]').count() == 1
        assert page.locator('a[href="/funcionarios/comissao"]').count() == 1
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
            for theme in ['fer-light', 'fer-slate', 'fer-night']:
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
    assert len(report) == 64 and all(row['status'] == 'passed' for row in report)
    print('64 real Blade/browser scenarios passed with no JavaScript exceptions.')


if __name__ == '__main__':
    main()

