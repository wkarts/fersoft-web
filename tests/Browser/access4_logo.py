"""Logo opcional sobre as Blades reais exportadas pelo teste existente.
Não emite requisições para a aplicação instalada nem autentica usuários reais.
"""
from __future__ import annotations

import argparse
import json
import mimetypes
import subprocess
from pathlib import Path
from urllib.parse import unquote, urlparse

from playwright.sync_api import sync_playwright

SIZES = [(1666, 874), (1024, 600), (961, 600), (960, 740), (390, 844), (320, 480), (500, 500)]
PROFILES = {
    'desktop-only': {'desktop': {'width': 240, 'align': 'right', 'position': 'after', 'inset': 16}},
    'mobile-only': {'mobile': {'width': 112, 'height': 64, 'align': 'center', 'position': 'before'}},
    'maximum': {
        'desktop': {'width': 480, 'height': 320, 'align': 'right', 'position': 'after', 'inset': 64, 'space_before': 96, 'space_after': 96},
        'mobile': {'width': 240, 'height': 180, 'align': 'right', 'position': 'after', 'inset': 64, 'space_before': 96, 'space_after': 96},
    },
    'width-only': {'desktop': {'width': 480, 'align': 'left', 'position': 'before'}, 'mobile': {'width': 240, 'align': 'left'}},
    'height-only': {'desktop': {'height': 320, 'align': 'center'}, 'mobile': {'height': 180, 'align': 'center'}},
}
APPLY = """data => {
 const b = document.body;
 for (const a of Array.from(b.attributes)) if (a.name.startsWith('data-logo-')) b.removeAttribute(a.name);
 for (const p of Array.from(b.style)) if (p.startsWith('--login-logo-')) b.style.removeProperty(p);
 for (const [name, value] of Object.entries(data.attributes)) b.setAttribute(name, value);
 if (data.style) b.style.cssText += ';' + data.style;
}"""
MEASURE = """() => {
 const visible = e => e.getClientRects().length && e.getBoundingClientRect().width && e.getBoundingClientRect().height;
 const bounds = r => ({x:r.x,y:r.y,width:r.width,height:r.height,right:r.right,bottom:r.bottom});
 const rect = e => bounds(e.getBoundingClientRect());
 const logos = [...document.querySelectorAll('.login-identity img,.login-form-mobile-brand img')].filter(visible);
 const bad = [...document.querySelectorAll('html,body,.login-shell,.login-public,.login-auth,.login-form-container,.login-identity,.login-form-mobile-brand')]
   .filter(e=>visible(e) && e.clientWidth && e.scrollWidth>e.clientWidth+1).map(e=>e.className||e.tagName);
 const controls = [...document.querySelectorAll('.login-group input,.login-actions,.login-button')].filter(visible).map(rect);
 // O layout compacto aprovado reserva padding lateral no h2 para a logo.
 // Medir os glifos, não a caixa com esse espaço vazio, evita falso positivo
 // sem deixar de detectar qualquer sobreposição real do texto.
 for (const heading of [...document.querySelectorAll('.login-form-container h2')].filter(visible)) {
   const range = document.createRange();
   range.selectNodeContents(heading);
   controls.push(...Array.from(range.getClientRects()).map(bounds));
 }
 return {logos:logos.map(rect),controls,bad,rootWidth:document.documentElement.clientWidth,
   rootScroll:document.documentElement.scrollWidth,fit:logos.map(e=>getComputedStyle(e).objectFit)};
}"""


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--browsers', nargs='+', default=['chromium'])
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[2]
    fixtures = root / 'storage/framework/testing/access4-fixtures'
    output = root / 'storage/framework/testing/access4-browser-results'
    output.mkdir(parents=True, exist_ok=True)
    corpus = [x for x in json.loads((fixtures / 'manifest.json').read_text()) if x['name'].endswith('-poppins-empty.html')]
    assert len(corpus) == 9, 'Devem existir o padrão original e os oito modelos.'
    resolved = {}
    for name, config in PROFILES.items():
        code = 'require $argv[1]; echo json_encode(App\\Services\\LoginLogoSettings::presentation(json_decode($argv[2], true)));'
        resolved[name] = json.loads(subprocess.check_output([
            'php', '-r', code, str(root / 'app/Services/LoginLogoSettings.php'), json.dumps(config)
        ], text=True))
    report = {'source': 'Blades reais; atributos gerados pelo normalizador PHP real; sem rede externa', 'cases': [], 'failures': [], 'interactions': [], 'page_errors': [], 'missing_resources': []}
    with sync_playwright() as p:
        for engine in args.browsers:
            browser = getattr(p, engine).launch(headless=True)
            context = browser.new_context(service_workers='block', reduced_motion='reduce')

            def serve(route):
                path = unquote(urlparse(route.request.url).path)
                if path == '/__logo-editor':
                    controls = []
                    for scope in ['desktop', 'mobile']:
                        controls.append(f'<fieldset data-logo-controls>')
                        for field, maximum in [('width', 480 if scope == 'desktop' else 240), ('height', 320 if scope == 'desktop' else 180), ('inset', 64), ('space_before', 96), ('space_after', 96)]:
                            controls.append(f'<input name="logo[{scope}][{field}]" type="number" min="{24 if field in ["width", "height"] else 0}" max="{maximum}" data-logo-scope="{scope}" data-logo-field="{field}">')
                        for field, values in [('align', ['default', 'left', 'center', 'right']), ('position', ['default', 'before', 'after'])]:
                            options = ''.join(f'<option>{v}</option>' for v in values)
                            controls.append(f'<select name="logo[{scope}][{field}]" data-logo-scope="{scope}" data-logo-field="{field}">{options}</select>')
                        controls.append(f'<button type="button" data-logo-reset="{scope}">Restaurar</button></fieldset>')
                    route.fulfill(content_type='text/html; charset=utf-8', body='<form id="appearance-editor-form">' + ''.join(controls) + '</form><iframe id="appearance-preview-frame" width="1200" height="800" src="/__fixtures/default-poppins-empty.html"></iframe><script src="/js/login-logo-editor.js"></script>')
                    return
                if path.startswith('/__fixtures/'):
                    target = fixtures / Path(path).name
                else:
                    target = (root / 'public' / path.lstrip('/')).resolve()
                    if not target.is_relative_to(root / 'public'):
                        route.fulfill(status=404, body='Not found')
                        return
                if target.is_file():
                    route.fulfill(path=str(target), content_type=mimetypes.guess_type(str(target))[0] or 'application/octet-stream')
                elif path.startswith('/app-install/'):
                    route.fulfill(body='{}', content_type='application/json')
                elif path.endswith('/packs/js/sdk.js'):
                    route.fulfill(body='window.hubSDK={run:function(){}};', content_type='application/javascript')
                else:
                    if path.endswith(('.css', '.js', '.ttf', '.png', '.jpg', '.webp')):
                        report['missing_resources'].append(path)
                    route.fulfill(status=404, body='Not found')

            context.route('**/*', serve)
            page = context.new_page()
            page.on('pageerror', lambda error: report['page_errors'].append(str(error)))
            for fixture in corpus:
                page.goto('http://127.0.0.1:8765/__fixtures/' + fixture['name'], wait_until='load')
                page.evaluate('document.fonts.ready')
                for width, height in SIZES:
                    page.set_viewport_size({'width': width, 'height': height})
                    page.evaluate(APPLY, {'attributes': {}, 'style': ''})
                    page.evaluate('window.scrollTo(0,0)')
                    baseline = page.evaluate(MEASURE)
                    # A extensão sem configuração não pode modificar o visual anterior.
                    page.locator('link[href*="login-access4-logo.css"]').evaluate('(e)=>e.disabled=true')
                    assert page.evaluate(MEASURE) == baseline, 'CSS opcional alterou um modelo sem configuração'
                    page.locator('link[href*="login-access4-logo.css"]').evaluate('(e)=>e.disabled=false')
                    for name, data in resolved.items():
                        label = f'{engine}-{fixture["name"]}-{width}x{height}-{name}'
                        try:
                            page.evaluate(APPLY, data)
                            page.evaluate('window.scrollTo(0,0)')
                            metrics = page.evaluate(MEASURE)
                            assert not metrics['bad'], f'Overflow: {metrics["bad"]}'
                            assert metrics['rootScroll'] <= metrics['rootWidth'] + 1, 'Overflow do documento'
                            assert len(metrics['logos']) == 1, 'Logo duplicada ou oculta'
                            logo = metrics['logos'][0]
                            assert logo['x'] >= -1 and logo['right'] <= metrics['rootWidth'] + 1, 'Logo fora da largura disponível'
                            assert metrics['fit'] == ['contain'], 'A logo deve preservar a proporção visual'
                            if name == 'desktop-only' and width > 960:
                                assert abs(logo['width'] - 240) <= 1, 'Largura de desktop não aplicada'
                            if name == 'mobile-only' and width <= 960:
                                assert abs(logo['width'] - 112) <= 1 and abs(logo['height'] - 64) <= 1, 'Dimensões de mobile não aplicadas'
                            for c in metrics['controls']:
                                overlap_x = min(logo['right'], c['right']) - max(logo['x'], c['x'])
                                overlap_y = min(logo['bottom'], c['bottom']) - max(logo['y'], c['y'])
                                assert not (overlap_x > 1 and overlap_y > 1), 'Logo sobreposta ao formulário'
                            if (width <= 960 and name == 'desktop-only') or (width > 960 and name == 'mobile-only'):
                                assert metrics['logos'] == baseline['logos'], 'Configuração vazou para o outro dispositivo'
                            report['cases'].append(label)
                            if fixture['name'] == 'split-poppins-empty.html' and name == 'mobile-only' and width == 390:
                                page.screenshot(path=str(output / f'logo-{engine}-mobile.png'), full_page=True)
                            if fixture['name'] == 'split-poppins-empty.html' and name == 'desktop-only' and width == 1666:
                                page.screenshot(path=str(output / f'logo-{engine}-desktop.png'), full_page=True)
                        except Exception as exc:
                            report['failures'].append({'case': label, 'error': str(exc)})
            page.goto('http://127.0.0.1:8765/__logo-editor', wait_until='load')
            frame = page.frame_locator('#appearance-preview-frame')
            page.locator('[name="logo[desktop][width]"]').fill('240')
            assert frame.locator('body').get_attribute('data-logo-desktop-width') == '240'
            page.locator('[name="logo[mobile][width]"]').fill('96')
            assert frame.locator('body').get_attribute('data-logo-mobile-width') == '96'
            page.locator('[data-logo-reset="desktop"]').click()
            assert frame.locator('body').get_attribute('data-logo-desktop') is None
            assert frame.locator('body').get_attribute('data-logo-mobile-width') == '96'
            page.locator('[data-logo-reset="mobile"]').click()
            assert frame.locator('body').get_attribute('data-logo-mobile') is None
            report['interactions'].append(f'{engine}: preview, alteração e restauração independentes')
            browser.close()
    (output / 'logo-report.json').write_text(json.dumps(report, indent=2, ensure_ascii=False))
    print(json.dumps({k: len(report[k]) for k in ['cases', 'failures', 'interactions', 'page_errors', 'missing_resources']}))
    assert not report['failures'] and not report['page_errors'] and not report['missing_resources'], 'Regressão de logo detectada; consulte logo-report.json'


if __name__ == '__main__':
    main()
