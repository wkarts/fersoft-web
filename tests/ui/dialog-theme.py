"""Exercise the real dialog libraries against rendered refined Blade fixtures.

The fixtures contain only controlled values. No ERP action is submitted.
"""
from pathlib import Path
from urllib.parse import unquote, urlsplit
import http.server
import threading
import os
import re

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


def colors(page):
    return page.locator('body').evaluate('''body => {
        const sample = document.createElement('span');
        sample.style.cssText = 'background:var(--ui-accent);color:var(--ui-ink)';
        body.append(sample);
        const result = {accent: getComputedStyle(sample).backgroundColor,
            ink: getComputedStyle(sample).color};
        sample.remove();
        return result;
    }''')


def contrast(foreground, background):
    def luminance(value):
        channels = [int(channel) / 255 for channel in re.findall(r'\d+', value)[:3]]
        linear = [c / 12.92 if c <= .04045 else ((c + .055) / 1.055) ** 2.4 for c in channels]
        return sum(a * b for a, b in zip(linear, (.2126, .7152, .0722)))

    a, b = luminance(foreground), luminance(background)
    return (max(a, b) + .05) / (min(a, b) + .05)


def readable_button(button):
    styles = button.evaluate('''e => {
        const s = getComputedStyle(e);
        return {foreground:s.color, background:s.backgroundColor};
    }''')
    assert contrast(styles['foreground'], styles['background']) >= 4.5, f'Button contrast below 4.5:1: {styles}'


def contained(page, selector):
    box = page.locator(selector).bounding_box()
    assert box is not None
    assert box['x'] >= 0 and box['x'] + box['width'] <= page.viewport_size['width'] + 1, f'{selector} clipped horizontally'
    assert box['y'] >= 0 and box['y'] + box['height'] <= page.viewport_size['height'] + 1, f'{selector} clipped vertically'


def capture_dialog(page, kind, theme, width):
    if width in (1366, 390):
        page.evaluate('document.fonts.ready.then(() => true)')
        page.wait_for_timeout(350)  # Let the vendor entrance animation settle.
        page.screenshot(path=str(FIXTURES / f'dialog-{kind}-{theme}-{width}.png'))


def check_dialogs(page, theme, width):
    palette = colors(page)
    surface_rgb = page.locator('body').evaluate('''body => {
        const sample = document.createElement('span');
        sample.style.background = 'var(--ui-surface)';
        body.append(sample);
        const color = getComputedStyle(sample).backgroundColor;
        sample.remove();
        return color;
    }''')

    # This is the same SweetAlert signature used by /clientes Editar; assert
    # its original Boolean callback result after a visual change.
    page.evaluate('''() => {
        window.dialogChoice = 'pending';
        swal('Editar?', 'Deseja editar este registro?', 'warning')
            .then(choice => { window.dialogChoice = choice; });
    }''')
    classic = page.locator('.swal-overlay--show-modal .swal-modal')
    classic.wait_for(state='visible')
    contained(page, '.swal-overlay--show-modal .swal-modal')
    assert classic.locator('.swal-title').inner_text() == 'Editar?'
    assert classic.evaluate('(e)=>getComputedStyle(e).backgroundColor') == surface_rgb
    assert classic.locator('.swal-title').evaluate('(e)=>getComputedStyle(e).color') == palette['ink']
    confirm = classic.locator('.swal-button').first
    assert confirm.evaluate('(e)=>getComputedStyle(e).backgroundColor') == palette['accent'], 'Classic confirm remains vendor blue'
    readable_button(confirm)
    confirm.focus()
    assert confirm.evaluate('''e => {
        const style = getComputedStyle(e);
        return style.outlineStyle !== 'none' || style.boxShadow !== 'none';
    }'''), 'Classic focus must be visible'
    capture_dialog(page, 'sweetalert', theme, width)
    confirm.click()
    page.wait_for_function('window.dialogChoice === true')
    classic.wait_for(state='hidden')

    # Optional cancel button exists in real operational confirmations; preserve
    # the false result, without navigating or submitting anything.
    page.evaluate('''() => {
        window.cancelChoice = 'pending';
        swal({title:'Confirmar', text:'Deseja continuar?', icon:'warning',
            buttons:['Cancelar', 'Confirmar']})
            .then(choice => { window.cancelChoice = choice; });
    }''')
    cancel = page.locator('.swal-overlay--show-modal .swal-button--cancel')
    cancel.wait_for(state='visible')
    assert cancel.evaluate('(e)=>getComputedStyle(e).backgroundColor') != palette['accent']
    readable_button(cancel)
    cancel.click()
    page.wait_for_function('window.cancelChoice === null || window.cancelChoice === false')

    # SweetAlert2 is used for password and validation flows; no password is
    # entered and neither confirmation nor a backend request is performed.
    page.evaluate('''() => {
        window.sweet2Choice = 'pending';
        Swal.fire({title:'Estorno', text:'Informe a senha', icon:'warning',
            input:'password', showCancelButton:true, confirmButtonText:'Confirmar',
            cancelButtonText:'Cancelar', inputValidator:value => !value && 'Senha obrigatória'})
            .then(result => { window.sweet2Choice = result.dismiss || result.value; });
    }''')
    modern = page.locator('.swal2-container .swal2-popup')
    modern.wait_for(state='visible')
    contained(page, '.swal2-container .swal2-popup')
    assert modern.evaluate('(e)=>getComputedStyle(e).backgroundColor') == surface_rgb
    assert modern.locator('.swal2-title').evaluate('(e)=>getComputedStyle(e).color') == palette['ink']
    assert modern.locator('input.swal2-input').get_attribute('type') == 'password'
    modern.locator('.swal2-confirm').click()
    validation = modern.locator('.swal2-validation-message')
    validation.wait_for(state='visible')
    assert 'Senha obrigatória' in validation.inner_text()
    assert modern.locator('.swal2-input').evaluate('''e =>
        getComputedStyle(e).outlineColor ===
        getComputedStyle(e.closest('.swal2-popup').querySelector('.swal2-validation-message')).color
    '''), 'Invalid password field has a competing focus color'
    # A click leaves the pointer hovering the button, which correctly changes
    # its green shade to --ui-accent-hover. Move away to compare its base color.
    page.mouse.move(0, 0)
    assert modern.locator('.swal2-confirm').evaluate('(e)=>getComputedStyle(e).backgroundColor') == palette['accent'], 'SweetAlert2 confirm remains vendor blue'
    readable_button(modern.locator('.swal2-confirm'))
    readable_button(modern.locator('.swal2-cancel'))
    capture_dialog(page, 'sweetalert2', theme, width)
    modern.locator('.swal2-cancel').click()
    page.wait_for_function('window.sweet2Choice === "cancel"')
    modern.wait_for(state='hidden')

    page.locator('[data-target="#fixture-modal"]').click()
    modal = page.locator('#fixture-modal')
    modal.wait_for(state='visible')
    contained(page, '#fixture-modal .modal-dialog')
    assert modal.locator('.modal-content').evaluate('(e)=>getComputedStyle(e).backgroundColor') == surface_rgb
    assert modal.locator('.modal-title').evaluate('(e)=>getComputedStyle(e).color') == palette['ink']
    assert modal.locator('#fixture-name').input_value() == 'Preservado'
    modal.locator('[data-dismiss="modal"]').click()
    modal.wait_for(state='hidden')

    # Several real ERP views still contain inline green headers and bg-white
    # footers. The refined shell must make them readable in every preset while
    # leaving the original Bootstrap dismissal and form controls untouched.
    page.locator('#fixture-legacy-modal-trigger').click()
    legacy = page.locator('#fixture-legacy-modal')
    legacy.wait_for(state='visible')
    contained(page, '#fixture-legacy-modal .modal-dialog')
    assert legacy.locator('.modal-header').evaluate('(e)=>getComputedStyle(e).backgroundColor') == surface_rgb
    assert legacy.locator('.modal-title').evaluate('(e)=>getComputedStyle(e).color') == palette['ink']
    assert legacy.locator('.close').evaluate('(e)=>getComputedStyle(e).color') == palette['ink']
    assert legacy.locator('.modal-body').evaluate('(e)=>getComputedStyle(e).backgroundColor') == surface_rgb
    assert legacy.locator('#fixture-legacy-input').input_value() == 'Preservado'
    legacy.locator('.modal-header').evaluate('(e)=>e.classList.add("bg-danger")')
    danger_background = page.locator('body').evaluate('''body => {
        const sample = document.createElement('span');
        sample.style.background = 'var(--ui-danger-bg)';
        body.append(sample);
        const result = getComputedStyle(sample).backgroundColor;
        sample.remove();
        return result;
    }''')
    assert legacy.locator('.modal-header').evaluate('(e)=>getComputedStyle(e).backgroundColor') == danger_background
    footer_buttons = legacy.locator('.modal-footer .btn')
    first, second = footer_buttons.nth(0).bounding_box(), footer_buttons.nth(1).bounding_box()
    assert first is not None and second is not None
    assert first['x'] + first['width'] <= second['x'] + 1 or first['y'] + first['height'] <= second['y'] + 1, 'Modal actions overlap'
    contained(page, '#fixture-legacy-modal .modal-footer')
    legacy.locator('.modal-header [data-dismiss="modal"]').click()
    legacy.wait_for(state='hidden')


def main():
    assert all((FIXTURES / f'{theme}-lateral-comfortable.html').exists()
               for theme in ['fer-light', 'fer-slate', 'fer-night', 'fer-mist', 'fer-graphite']), 'Render Blade fixtures first'
    server = http.server.ThreadingHTTPServer(('127.0.0.1', 8765), Handler)
    threading.Thread(target=server.serve_forever, daemon=True).start()
    try:
        with sync_playwright() as playwright:
            options = {'executable_path': os.environ['UI_CHROMIUM']} if os.getenv('UI_CHROMIUM') else {}
            browser = playwright.chromium.launch(headless=True, **options)
            try:
                for theme in ['fer-light', 'fer-slate', 'fer-night', 'fer-mist', 'fer-graphite']:
                    for width, height in [(1366, 768), (768, 1024), (390, 844)]:
                        page = browser.new_page(viewport={'width': width, 'height': height}, reduced_motion='reduce')
                        errors = []
                        page.on('pageerror', lambda error: errors.append(str(error)))
                        page.route('https://**/*', lambda route: route.abort())
                        try:
                            page.add_init_script("localStorage.setItem('tour-slym', 'true')")
                            page.goto(f'http://127.0.0.1:8765/fixtures/{theme}-lateral-comfortable.html', wait_until='domcontentloaded')
                            page.wait_for_function('typeof swal === "function" && typeof Swal !== "undefined" && typeof jQuery !== "undefined"')
                            check_dialogs(page, theme, width)
                            assert not errors, 'JavaScript exception: ' + '; '.join(errors)
                            print(theme, f'{width}x{height}', 'dialogs passed', flush=True)
                        finally:
                            page.close()
            finally:
                browser.close()
    finally:
        server.shutdown()
        server.server_close()


if __name__ == '__main__':
    main()
