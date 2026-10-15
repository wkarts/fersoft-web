"""CI-only browser checks on HTML rendered from production Blade + real legacy widget bundles.
These are component/layout checks, not authenticated end-to-end business homologation.
"""
from pathlib import Path
import http.server,threading,json,os,sys
from playwright.sync_api import sync_playwright
ROOT=Path(__file__).resolve().parents[2]
FIXTURES=ROOT/'storage/app/ui-browser'
class Handler(http.server.SimpleHTTPRequestHandler):
    def translate_path(self,path):
        from urllib.parse import unquote,urlsplit
        rel=unquote(urlsplit(path).path).lstrip('/')
        if '..' in Path(rel).parts: return str(ROOT/'public/404')
        return str(FIXTURES/rel[9:]) if rel.startswith('fixtures/') else str(ROOT/'public'/rel)
    def log_message(self,*args): pass
server=http.server.ThreadingHTTPServer(('127.0.0.1',8765),Handler)
threading.Thread(target=server.serve_forever,daemon=True).start()
report=[]
try:
 with sync_playwright() as p:
  browser=p.chromium.launch(headless=True,**({'executable_path':os.environ['UI_CHROMIUM']} if os.getenv('UI_CHROMIUM') else {}))
  for theme in ['fer-light','fer-slate','fer-night']:
   for layout in ['lateral','superior']:
    for density in ['comfortable','compact']:
     for width,height in [(1440,1000),(390,844)]:
      page=browser.new_page(viewport={'width':width,'height':height},reduced_motion='reduce')
      errors=[]
      page.on('pageerror', lambda error: errors.append(str(error)))
      # No customer APIs or external telemetry are accessed by a test.
      page.route('https://**/*', lambda route: route.abort())
      filename=f'{theme}-{layout}-{density}.html'
      page.goto(f'http://127.0.0.1:8765/fixtures/{filename}',wait_until='domcontentloaded')
      page.wait_for_timeout(300)
      assert page.locator('h1').first.inner_text()=='Dashboard Analítico Executivo'
      assert '6.479.876,12' in page.locator('.ui-metrics').inner_text()
      assert page.locator('.ui-chart-empty').count()==2
      assert page.locator('#ui-navigation a[href="/logs"]').count()==0
      assert page.locator('a[href="/login/logoff"]').count()==1
      assert page.locator('#set-location-top').count()==1
      assert page.locator('#btn-whatsapp-global').count()==1
      assert page.locator('#dataHoraClock').count()==1
      assert page.locator('.ui-finance-link').count()==4
      assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth + 2'),f'Horizontal overflow {filename} {width}'
      if width<992:
       page.locator('[data-ui-menu-open]').click()
       assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-nav-mobile-open")')
       assert page.locator('#kt_wrapper').evaluate('(e)=>e.inert')
       page.keyboard.press('Escape')
       assert not page.locator('#kt_wrapper').evaluate('(e)=>e.inert')
      else:
       link=page.locator('#ui-navigation .menu-toggle').filter(has_text='Cadastros').first
       link.click()
       assert link.get_attribute('aria-expanded')=='true'
       link.click()
       assert link.get_attribute('aria-expanded')=='false'
       if layout=='lateral':
        page.locator('[data-ui-collapse]').click()
        assert page.locator('body').evaluate('(e)=>e.classList.contains("ui-nav-collapsed")')
        page.locator('[data-ui-collapse]').click()
      page.keyboard.press('Control+k')
      assert page.locator('#ui-command-palette').is_visible()
      page.locator('#ui-command-search').fill('Financeiro')
      assert page.locator('.ui-command__result:visible').count()>0
      page.keyboard.press('Escape')
      assert not page.locator('#ui-command-palette').is_visible()
      page.locator('[data-target="#fixture-modal"]').click()
      page.wait_for_timeout(200)
      assert page.locator('#fixture-modal').is_visible()
      assert page.locator('#fixture-name').input_value()=='Preservado'
      page.locator('#fixture-modal [data-dismiss="modal"]').click()
      page.wait_for_timeout(200)
      assert not page.locator('#fixture-modal').is_visible()
      assert page.locator('#fixture-select').evaluate('(e)=>jQuery(e).hasClass("select2-hidden-accessible")')
      assert page.locator('#fixture-grid').evaluate('(e)=>jQuery(e).closest(".datatable").data("KTDatatable") != null')
      if density=='comfortable': page.screenshot(path=str(FIXTURES/f'{theme}-{layout}-{width}.png'),full_page=True)
      # Legacy endpoint polling may produce known exceptions; preserve a full diagnostic, never mark errors clean.
      report.append({'fixture':filename,'viewport':width,'layout_checks':'passed','javascript_errors':errors})
      page.close()
  browser.close()
finally:
 server.shutdown()
 (FIXTURES/'browser-results.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
print(f'{len(report)} native UI browser scenarios passed; errors recorded separately.')
