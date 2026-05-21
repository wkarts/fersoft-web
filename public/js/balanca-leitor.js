(function () {
  const widget = document.querySelector('[data-balanca-widget="1"]');
  if (!widget || !window.AdpRuntimeClient) return;

  const select = widget.querySelector('[data-balanca-select]');
  const pesoEl = widget.querySelector('[data-peso]');
  const statusEl = widget.querySelector('[data-status]');
  const resumoEl = widget.querySelector('[data-peso-resumo]');
  const cameraArea = widget.querySelector('[data-camera-area]');
  const cameraList = widget.querySelector('[data-camera-preview-list]');
  const inputBalanca = widget.querySelector('input[name="balanca_config_id"]');
  const inputEvidence = widget.querySelector('input[name="balanca_evidence_json"]');
  let timer = null;
  let selectedBalanca = null;

  function log(msg) {
    const logEl = document.getElementById('balanca-log');
    if (logEl) logEl.textContent += `\n[${new Date().toLocaleTimeString()}] ${msg}`;
  }

  function selected() {
    const option = select?.options[select.selectedIndex];
    return window.AdpRuntimeClient.buildBalancaFromSelectOption(option);
  }

  function updateRead(r) {
    pesoEl.textContent = r.peso_formatado || '0';
    resumoEl.textContent = `${r.peso || 0} ${r.unidade || 'kg'}`;
    statusEl.textContent = r.estavel ? 'ESTÁVEL' : (r.success ? 'OSCILANDO' : 'OFFLINE');
  }

  async function read() {
    selectedBalanca = selected();
    if (!selectedBalanca) return null;
    const r = await window.AdpRuntimeClient.readScale(selectedBalanca);
    updateRead(r);
    return r;
  }

  function renderCameras(evidence) {
    const cameras = evidence?.cameras || [];
    if (!cameraArea || !cameraList) return;

    cameraArea.style.display = cameras.length ? '' : 'none';
    cameraList.innerHTML = '';

    cameras.forEach(function (camera) {
      const response = camera.response || {};
      const url = response.snapshot_url || response.url || response.image_url || response?.data?.snapshot_url || response?.data?.url || null;
      const base64 = response.base64 || response.image_base64 || response?.data?.base64 || null;
      const src = url || (base64 ? `data:image/jpeg;base64,${String(base64).replace(/^data:image\/\w+;base64,/, '')}` : null);
      const col = document.createElement('div');
      col.className = 'col-md-6 mb-2';
      col.innerHTML = src
        ? `<img src="${src}" class="img-fluid rounded border" alt="Snapshot câmera ADP"><small class="d-block text-muted mt-1">${camera.uuid}</small>`
        : `<div class="alert alert-warning py-2 mb-0">Sem imagem: ${camera.uuid}</div>`;
      cameraList.appendChild(col);
    });
  }

  if (select) {
    select.addEventListener('change', function () {
      selectedBalanca = selected();
      if (inputBalanca) inputBalanca.value = selectedBalanca?.id || '';
      if (inputEvidence) inputEvidence.value = '';
      if (cameraArea) cameraArea.style.display = 'none';
    });
  }

  widget.querySelectorAll('[data-action]').forEach(btn => btn.addEventListener('click', async () => {
    selectedBalanca = selected();
    if (!selectedBalanca) {
      log('Selecione uma balança ADP.');
      return;
    }

    try {
      const action = btn.dataset.action;
      if (action === 'open') {
        await window.AdpRuntimeClient.openScale(selectedBalanca);
        log('Balança conectada.');
        if (timer) clearInterval(timer);
        timer = setInterval(() => read().catch(e => log(e.message)), 1000);
      }
      if (action === 'close') {
        await window.AdpRuntimeClient.closeScale(selectedBalanca);
        if (timer) clearInterval(timer);
        log('Balança desconectada.');
      }
      if (action === 'read') {
        await read();
        log('Peso capturado.');
      }
      if (action === 'evidence') {
        const evidence = await window.AdpRuntimeClient.captureEvidence(selectedBalanca);
        updateRead({
          success: evidence.success,
          peso: evidence.peso.valor,
          peso_formatado: evidence.peso.formatado,
          unidade: evidence.peso.unidade,
          estavel: evidence.peso.estavel,
        });
        if (inputEvidence) inputEvidence.value = JSON.stringify(evidence);
        renderCameras(evidence);
        log('Evidência capturada.');
      }
    } catch (e) {
      log(e.message || 'Falha na operação ADP.');
    }
  }));
})();
