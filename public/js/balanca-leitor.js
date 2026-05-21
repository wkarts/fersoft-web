(function () {
  const widgets = document.querySelectorAll('[data-balanca-widget="1"]');
  if (!widgets.length || !window.AdpRuntimeClient) return;

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
    });
  }

  function imageSrcFromCamera(camera) {
    if (!camera) return '';

    if (camera.image_data_url) return camera.image_data_url;
    if (camera.image_src) return camera.image_src;
    if (camera.image_url) return camera.image_url;

    const response = camera.response || {};
    if (window.AdpRuntimeClient && typeof window.AdpRuntimeClient.imageSrcFromSnapshotResponse === 'function') {
      const src = window.AdpRuntimeClient.imageSrcFromSnapshotResponse(response);
      if (src) return src;
    }

    const snapshot = response.snapshot || response.data?.snapshot || response.result?.snapshot || {};
    const raw = snapshot.image_data_url || response.image_data_url || snapshot.base64 || response.base64 || snapshot.image_base64 || response.image_base64 || '';
    if (!raw) return '';
    if (String(raw).startsWith('data:image')) return raw;
    return 'data:image/jpeg;base64,' + String(raw).replace(/^data:image\/\w+;base64,/, '');
  }

  function ensureModal(widget) {
    let modal = widget.parentElement?.querySelector('[data-balanca-image-modal]') || document.querySelector('[data-balanca-image-modal]');
    if (!modal) {
      modal = document.createElement('div');
      modal.className = 'adp-balanca-image-modal';
      modal.setAttribute('data-balanca-image-modal', '1');
      modal.innerHTML = `
        <div class="adp-balanca-image-dialog">
          <div class="adp-balanca-image-header">
            <strong data-balanca-image-title>Imagem da câmera</strong>
            <button type="button" data-balanca-image-close aria-label="Fechar">&times;</button>
          </div>
          <div class="adp-balanca-image-body"><img data-balanca-image-img alt="Imagem da câmera ADP"></div>
        </div>`;
      document.body.appendChild(modal);
    }

    if (!modal.dataset.bound) {
      modal.dataset.bound = '1';
      modal.addEventListener('click', function (event) {
        if (event.target === modal || event.target.matches('[data-balanca-image-close]')) {
          modal.classList.remove('show');
        }
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') modal.classList.remove('show');
      });
    }

    return modal;
  }

  function openImageModal(widget, src, title) {
    if (!src) return;
    const modal = ensureModal(widget);
    const titleEl = modal.querySelector('[data-balanca-image-title]');
    const imgEl = modal.querySelector('[data-balanca-image-img]');
    if (titleEl) titleEl.textContent = title || 'Imagem da câmera';
    if (imgEl) imgEl.src = src;
    modal.classList.add('show');
  }

  widgets.forEach(function (widget) {
    if (widget.dataset.balancaLeitorBound === '1') return;
    widget.dataset.balancaLeitorBound = '1';

    const select = widget.querySelector('[data-balanca-select]');
    const pesoEl = widget.querySelector('[data-peso]');
    const statusEl = widget.querySelector('[data-status]');
    const resumoEl = widget.querySelector('[data-peso-resumo]');
    const cameraArea = widget.querySelector('[data-camera-area]');
    const cameraList = widget.querySelector('[data-camera-preview-list]');
    const inputBalanca = widget.querySelector('input[name="balanca_config_id"]');
    const inputEvidence = widget.querySelector('input[name="balanca_evidence_json"]');
    const logEl = widget.querySelector('[data-balanca-log]');
    let timer = null;
    let selectedBalanca = null;

    function log(msg) {
      if (!logEl) return;
      logEl.textContent += `\n[${new Date().toLocaleTimeString()}] ${msg}`;
      logEl.scrollTop = logEl.scrollHeight;
    }

    function selected() {
      const option = select?.options[select.selectedIndex];
      return window.AdpRuntimeClient.buildBalancaFromSelectOption(option);
    }

    function updateRead(r) {
      if (!r) return;
      if (pesoEl) pesoEl.textContent = r.peso_formatado || '0';
      if (resumoEl) resumoEl.textContent = `${r.peso || 0} ${r.unidade || 'kg'}`;
      if (statusEl) statusEl.textContent = r.estavel ? 'ESTÁVEL' : (r.success ? 'OSCILANDO' : 'OFFLINE');
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

      cameras.forEach(function (camera, index) {
        const src = imageSrcFromCamera(camera);
        const label = camera.descricao || camera.name || `Câmera ${index + 1}`;
        const uuid = camera.uuid || camera.camera_uuid || '';
        const item = document.createElement('div');
        item.className = 'mb-2';
        item.innerHTML = src
          ? `<button type="button" class="adp-camera-thumb-btn" data-img="${escapeHtml(src)}" data-title="${escapeHtml(label)}">
               <img src="${escapeHtml(src)}" class="adp-camera-thumb-img" alt="Snapshot câmera ADP">
               <span><span class="adp-camera-thumb-title">${escapeHtml(label)}</span><span class="adp-camera-thumb-meta">${escapeHtml(uuid)}</span></span>
             </button>`
          : `<div class="alert alert-warning adp-camera-empty">Sem imagem: ${escapeHtml(uuid || label)}</div>`;
        const btn = item.querySelector('[data-img]');
        if (btn) {
          btn.addEventListener('click', () => openImageModal(widget, btn.dataset.img, btn.dataset.title));
        }
        cameraList.appendChild(item);
      });
    }

    if (select) {
      select.addEventListener('change', function () {
        selectedBalanca = selected();
        if (inputBalanca) inputBalanca.value = selectedBalanca?.id || '';
        if (inputEvidence) inputEvidence.value = '';
        if (cameraArea) cameraArea.style.display = 'none';
        if (cameraList) cameraList.innerHTML = '';
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
          timer = null;
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
  });
})();
