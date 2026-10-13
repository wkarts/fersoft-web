"""Regressão real de layout com Blades exportadas pelo PHPUnit.

Nenhum login real é executado: as respostas de rede de OTP/recuperação são
simuladas. HTML, CSS, JS, imagens e fontes vêm dos arquivos reais da aplicação.
"""
from __future__ import annotations

import argparse
import json
import mimetypes
from pathlib import Path
from urllib.parse import parse_qs, unquote, urlparse

from playwright.sync_api import Page, Route, expect, sync_playwright

SIZES = [(1920, 1080), (1666, 874), (1366, 650), (1024, 600), (961, 600),
         (960, 740), (931, 970), (768, 1024), (500, 500), (390, 844),
         (360, 640), (320, 568), (844, 390), (390, 320), (320, 480)]
METRICS = """() => {
 const names = ['html','body','.login-shell','.login-public','.login-auth',
                '.login-form-container','.login-hero-content','.login-content-blocks',
                '.login-publication','.login-mobile-updates'];
 const bad = [];
 for (const selector of names) for (const el of document.querySelectorAll(selector)) {
   if (!el.getClientRects().length || !el.clientWidth || el.closest('details:not([open])') && !el.matches('details')) continue;
   if (el.scrollWidth > el.clientWidth + 1) bad.push({selector, width:el.clientWidth, scroll:el.scrollWidth});
 }
 const root = document.documentElement;
 const nested = Array.from(document.querySelectorAll('.login-public,.login-auth')).filter(e=>e.getClientRects().length && ['auto','scroll','hidden','clip'].includes(getComputedStyle(e).overflowY)).map(e=>e.className);
 return {nested, bad, width:root.clientWidth, scroll:root.scrollWidth, height:root.scrollHeight,
         viewportHeight:innerHeight, panelHeight:document.querySelector('.login-auth').getBoundingClientRect().height};
}"""


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--root', type=Path, default=Path(__file__).resolve().parents[2])
    parser.add_argument('--browsers', nargs='+', default=['chromium'])
    parser.add_argument('--chromium-path')
    args = parser.parse_args()
    root = args.root.resolve()
    fixture_dir = root / 'storage/framework/testing/access4-fixtures'
    output = root / 'storage/framework/testing/access4-browser-results'
    output.mkdir(parents=True, exist_ok=True)
    fixtures = json.loads((fixture_dir / 'manifest.json').read_text())
    report: dict = {'source': 'actual Blade, local repository fonts, mocked network', 'cases': [], 'failures': [], 'interactions': [], 'resources': []}
    captured_posts: list[dict] = []
    page_errors: list[str] = []

    def serve(route: Route) -> None:
        request = route.request
        path = unquote(urlparse(request.url).path)
        if path == '/__editor-fixture':
            # Shell de teste do editor; iframe utiliza a Blade real exportada.
            route.fulfill(content_type='text/html; charset=utf-8', body="""<!doctype html><html><head><meta charset="utf-8"></head><body>
<div id="appearance-admin"><form id="appearance-editor-form">
<select name="layout"><option value="split">Dividido</option><option value="wide">Panorâmico</option></select>
<input name="headline" value="Prévia"><input name="tagline" value="Descrição">
<input name="accent_color" value="#16885d"><input name="background_color" value="#f3f8f5">
<input type="checkbox" name="show_hero" checked><input type="checkbox" name="show_posts" checked>
<input type="checkbox" name="show_phrases" checked><input type="checkbox" name="show_signup" checked>
<input type="checkbox" name="show_support" checked></form>
<section><div style="width:380px;border:1px solid;overflow:hidden">
<iframe id="appearance-preview-frame" src="/__fixtures/default-poppins-empty.html"></iframe>
</div></section></div><script src="/js/login-appearance-admin.js"></script></body></html>""")
            return
        if path.startswith('/__fixtures/'):
            target = (fixture_dir / path.rsplit('/', 1)[-1]).resolve()
            if target.parent == fixture_dir and target.is_file():
                route.fulfill(path=str(target), content_type='text/html; charset=utf-8')
                return
        if request.method == 'POST' and path in ['/otp/verify-login', '/login/request', '/recuperarSenha']:
            data = parse_qs(request.post_data or '')
            captured_posts.append({'path': path, 'data': data})
            if path == '/otp/verify-login':
                valid = data.get('otp_code') == ['123456']
                route.fulfill(status=200 if valid else 422, json={'valid': valid, 'error': 'Código OTP inválido.'})
            else:
                route.fulfill(body='<h1>Envio de teste recebido</h1>', content_type='text/html')
            return
        target = (root / 'public' / path.lstrip('/')).resolve()
        if target.is_relative_to(root / 'public') and target.is_file():
            route.fulfill(path=str(target), content_type=mimetypes.guess_type(str(target))[0] or 'application/octet-stream')
            return
        if path.endswith('/packs/js/sdk.js'):
            route.fulfill(body='window.hubSDK={run:function(){}};', content_type='application/javascript')
        elif path.startswith('/app-install/'):
            route.fulfill(body='{}', content_type='application/json')
        else:
            if path.endswith(('.css', '.js', '.ttf', '.woff', '.woff2', '.png', '.jpg', '.webp')):
                report['resources'].append(request.url)
            route.fulfill(status=404, body='Not found')

    def load(page: Page, fixture: dict) -> None:
        page.goto('http://127.0.0.1:8765/__fixtures/' + fixture['name'], wait_until='load')
        page.evaluate('document.fonts.ready')
        assert page.evaluate("document.fonts.check(\"400 16px 'LoginTypeface'\") && document.fonts.check(\"600 16px 'LoginTypeface'\")"), 'Fontes locais não carregadas'
        page.evaluate('window.scrollTo(0,0)')

    def check(page: Page) -> dict:
        metrics = page.evaluate(METRICS)
        assert not metrics['nested'], f"Rolagem/recorte aninhado nas colunas: {metrics['nested']}"
        assert not metrics['bad'], f"Transbordamento horizontal: {metrics['bad']}"
        return metrics

    with sync_playwright() as playwright:
        for engine in args.browsers:
            options = {'headless': True}
            if engine == 'chromium' and args.chromium_path:
                options['executable_path'] = args.chromium_path
            browser = getattr(playwright, engine).launch(**options)
            context = browser.new_context(service_workers='block', reduced_motion='reduce')
            context.route('**/*', serve)
            page = context.new_page()
            page.set_default_timeout(5000)
            page.on('pageerror', lambda error: page_errors.append(str(error)))
            for fixture in fixtures:
                for width, height in SIZES:
                    label = f"{engine}-{fixture['name']}-{width}x{height}"
                    try:
                        page.set_viewport_size({'width': width, 'height': height})
                        load(page, fixture)
                        metrics = check(page)
                        if width <= 960:
                            expect(page.locator('.login-public')).to_be_hidden()
                            expect(page.locator('.login-form-mobile-brand')).to_be_visible()
                            if fixture['scenario'] == 'content':
                                expect(page.locator('.login-mobile-updates')).to_be_visible()
                                assert not page.locator('.login-mobile-updates').evaluate('(e)=>e.open')
                            if width >= 360 and height >= 640:
                                assert metrics['height'] <= height + 1, f"Rolagem vertical dispensável: {metrics}"
                        if width >= 961 and height >= 600 and fixture['scenario'] == 'empty':
                            assert metrics['height'] <= height + 1, f"Rolagem desktop dispensável: {metrics}"
                        # Nenhum campo/botão é cortado: todos podem ser alcançados pelo documento.
                        for selector in ['#login', '#senha', '#kt_login_signin_submit', 'a[href$="/cadastro"]']:
                            node = page.locator(selector)
                            node.scroll_into_view_if_needed()
                            expect(node).to_be_in_viewport(ratio=1)
                        if width == 500 and height == 500:
                            page.evaluate('window.scrollTo(0,0)')
                            expect(page.locator('#kt_login_signin_submit')).to_be_in_viewport(ratio=1)
                        if fixture['layout'] == 'split' and fixture['font'] == 'poppins' and fixture['scenario'] == 'empty' and (width, height) in [(1666,874),(931,970),(500,500),(390,844),(320,568)]:
                            page.evaluate('window.scrollTo(0,0)')
                            page.screenshot(path=str(output / f'{engine}-{width}x{height}.png'), full_page=True)
                        if fixture['font'] == 'poppins' and fixture['scenario'] == 'empty' and (width,height) == (1366,650):
                            page.evaluate('window.scrollTo(0,0)')
                            page.screenshot(path=str(output / f"{engine}-{fixture['name']}.png"), full_page=True)
                        report['cases'].append({'case':label,'status':'passed','rootHeight':metrics['height']})
                    except Exception as error:
                        report['failures'].append({'case':label,'error':str(error)})
                        if len(report['failures']) <= 8:
                            page.screenshot(path=str(output / f'failure-{len(report["failures"])}.png'), full_page=True)

            fixture = next(f for f in fixtures if f['layout']=='split' and f['font']=='poppins' and f['scenario']=='content')
            try:
                page.set_viewport_size({'width':390,'height':640})
                load(page,fixture)
                page.locator('.login-mobile-updates summary').click()
                expect(page.locator('.login-mobile-updates .login-publication').first).to_be_visible()
                check(page)
                ids = page.locator('[id]').evaluate_all('(els)=>els.map(e=>e.id)')
                assert len(ids) == len(set(ids)), 'IDs duplicados'
                page.screenshot(path=str(output / f'{engine}-comunicados.png'), full_page=True)
                report['interactions'].append(engine + ': comunicados publicados acessíveis, IDs únicos, texto longo sem overflow')

                load(page,fixture)
                page.locator('#login-access4-recovery-toggle').click()
                expect(page.locator('#kt_login_signin_form')).to_be_hidden()
                page.locator('#login-access4-email').fill('teste@example.test')
                page.locator('#login-access4-recovery button[type=submit]').click()
                page.wait_for_url('**/recuperarSenha')
                assert captured_posts[-1]['path']=='/recuperarSenha'
                assert captured_posts[-1]['data']['email']==['teste@example.test']
                report['interactions'].append(engine + ': recuperação preserva endpoint e payload')

                load(page,fixture)
                page.locator('#login-access4-recovery-toggle').click()
                page.locator('#login-access4-recovery-back').click()
                expect(page.locator('#kt_login_signin_form')).to_be_visible()
                report['interactions'].append(engine + ': voltar da recuperação ao login')

                for height in [640,320]:
                    page.set_viewport_size({'width':390,'height':height})
                    load(page,fixture)
                    page.locator('#otpModal').evaluate('(e)=>e.showModal()')
                    page.locator('#otp-code').fill('000000')
                    page.locator('#btn-otp-validate').click()
                    expect(page.locator('#otp-error')).to_contain_text('Código OTP inválido.')
                    assert page.locator('#otpModal').evaluate('(e)=>e.scrollWidth <= e.clientWidth + 1')
                    page.screenshot(path=str(output / f'{engine}-otp-{height}.png'))
                    page.locator('#otp-code').fill('123456')
                    page.locator('#btn-otp-validate').click()
                    page.wait_for_url('**/login/request')
                    assert captured_posts[-1]['data']['otp_code']==['123456']
                    assert captured_posts[-1]['data']['login']==['browser-fixture-user']
                    report['interactions'].append(engine + f': OTP inválido/válido e envio histórico, viewport 390x{height}')
                page.set_viewport_size({'width':1100, 'height':900})
                page.goto('http://127.0.0.1:8765/__editor-fixture', wait_until='load')
                frame = page.frame_locator('#appearance-preview-frame')
                expect(frame.locator('.login-public')).to_be_visible()
                assert page.locator('#appearance-preview-frame').evaluate('(e)=>e.clientWidth') == 1200
                page.locator('[data-preview-device=mobile]').click()
                expect(frame.locator('.login-public')).to_be_hidden()
                assert page.locator('#appearance-preview-frame').evaluate('(e)=>e.clientWidth') == 390
                page.locator('[data-preview-device=desktop]').click()
                page.locator('select[name=layout]').select_option('wide')
                expect(frame.locator('body')).to_have_class('login-access4 login-layout-wide')
                report['interactions'].append(engine + ': editor preserva viewport desktop e alterna para celular')
            except Exception as error:
                report['failures'].append({'case':engine+'-interactions' ,'error':str(error)})
            browser.close()

    report['pageErrors'] = page_errors
    report['resources'] = sorted(set(report['resources']))
    (output / 'report.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
    print(f"Browser: {len(report['cases'])} cenários aprovados; {len(report['interactions'])} interações; {len(report['failures'])} falhas")
    if report['failures'] or page_errors or report['resources']:
        print(json.dumps({'failures':report['failures'][:10], 'pageErrors':page_errors[:10], 'resources':report['resources']},ensure_ascii=False,indent=2))
        raise SystemExit(1)

if __name__ == '__main__':
    main()
