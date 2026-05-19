(function(){
  const widget = document.querySelector('[data-balanca-widget="1"]'); if(!widget) return;
  const select = widget.querySelector('[data-balanca-select]');
  const pesoEl = widget.querySelector('[data-peso]');
  const statusEl = widget.querySelector('[data-status]');
  const resumoEl = widget.querySelector('[data-peso-resumo]');
  let timer = null;

  function log(msg){ const logEl = document.getElementById('balanca-log'); if(logEl){ logEl.textContent += `[${new Date().toLocaleTimeString()}] ${msg}\n`; }}
  async function call(path, method='GET'){
    const id = select.value; if(!id) return null;
    const resp = await fetch(`/balancas/leitor/${id}/${path}`, {method, headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}});
    return resp.json();
  }
  async function read(){ const r = await call('read'); if(!r) return; pesoEl.textContent = r.peso_formatado || '0'; resumoEl.textContent = `${r.peso || 0} ${r.unidade || 'kg'}`; statusEl.textContent = r.estavel ? 'ESTÁVEL' : (r.success ? 'OSCILANDO' : 'OFFLINE'); }
  widget.querySelectorAll('[data-action]').forEach(btn=>btn.addEventListener('click', async ()=>{ const action = btn.dataset.action; if(action==='open'){await call('open','POST'); if(timer) clearInterval(timer); timer=setInterval(read,1500);} if(action==='close'){await call('close','POST'); if(timer) clearInterval(timer);} if(action==='read'){await read();} if(action==='evidence'){const r=await call('evidence','POST'); log(JSON.stringify(r));}}));
})();
