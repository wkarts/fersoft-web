(function () {
  'use strict';

  function setBadge(el, status, text) {
    if (!el) return;
    const modern = el.classList && Array.from(el.classList).some(cls => cls.indexOf('status-') === 0);
    if (modern) {
      el.className = 'status-badge status-' + (status === 'online' ? 'online' : status === 'warning' ? 'pendente' : 'offline');
    } else {
      el.className = 'badge ' + (status === 'online' ? 'badge-success' : status === 'warning' ? 'badge-warning' : 'badge-danger');
    }
    el.textContent = text;
  }

  function rowLog(row, text) {
    const id = row && row.dataset ? row.dataset.cameraRow : '';
    const el = id ? document.querySelector('[data-camera-log="' + id + '"]') : null;
    if (el) {
      const now = new Date().toLocaleTimeString();
      el.textContent = '[' + now + '] ' + text + '\n' + el.textContent;
    }
  }

  function setCameraPreview(id, src) {
    const img = document.querySelector('[data-camera-preview="' + id + '"]');
    const empty = document.querySelector('[data-camera-preview-empty="' + id + '"]');

    if (!img) {
      return false;
    }

    if (!src) {
      if (empty) {
        empty.textContent = 'Imagem indisponível';
        empty.style.display = '';
      }
      img.removeAttribute('src');
      img.classList.remove('adp-camera-preview-loaded');
      img.style.display = 'none';
      return false;
    }

    img.onload = function () {
      img.classList.add('adp-camera-preview-loaded');
      img.style.display = 'block';
      if (empty) empty.style.display = 'none';
    };

    img.onerror = function () {
      img.classList.remove('adp-camera-preview-loaded');
      img.style.display = 'none';
      if (empty) {
        empty.textContent = 'Imagem indisponível';
        empty.style.display = '';
      }
    };

    img.src = src;
    img.classList.add('adp-camera-preview-loaded');
    img.style.display = 'block';
    if (empty) empty.style.display = 'none';
    return true;
  }

  function setCameraLivePreview(id, src, mode) {
    const img = document.querySelector('[data-camera-preview="' + id + '"]');
    const empty = document.querySelector('[data-camera-preview-empty="' + id + '"]');
    const modeBadge = document.querySelector('[data-camera-live-mode="' + id + '"]');

    if (!img || !src) {
      return false;
    }

    img.onload = function () {
      img.classList.add('adp-camera-preview-loaded');
      img.style.display = 'block';
      if (empty) empty.style.display = 'none';
      if (modeBadge) {
        modeBadge.textContent = mode ? ('Ao vivo: ' + mode.toUpperCase()) : 'Ao vivo';
        modeBadge.style.display = '';
      }
    };

    img.onerror = function () {
      img.classList.remove('adp-camera-preview-loaded');
      img.style.display = 'none';
      if (empty) {
        empty.textContent = 'Preview ao vivo indisponível';
        empty.style.display = '';
      }
      if (modeBadge) modeBadge.style.display = 'none';
    };

    img.src = src;
    img.classList.add('adp-camera-preview-loaded');
    img.style.display = 'block';
    if (empty) empty.style.display = 'none';
    if (modeBadge) {
      modeBadge.textContent = mode ? ('Ao vivo: ' + mode.toUpperCase()) : 'Ao vivo';
      modeBadge.style.display = '';
    }
    return true;
  }

  function stopCameraLivePreview(id) {
    const img = document.querySelector('[data-camera-preview="' + id + '"]');
    const modeBadge = document.querySelector('[data-camera-live-mode="' + id + '"]');

    if (img) {
      img.removeAttribute('src');
      img.classList.remove('adp-camera-preview-loaded');
      img.style.display = 'none';
    }

    if (modeBadge) {
      modeBadge.style.display = 'none';
      modeBadge.textContent = '';
    }
  }

  function imageFromSnapshot(response) {
    if (window.AdpRuntimeClient && typeof window.AdpRuntimeClient.imageSrcFromSnapshotResponse === 'function') {
      return window.AdpRuntimeClient.imageSrcFromSnapshotResponse(response);
    }

    const candidates = [response?.image_data_url, response?.data_url, response?.image_url, response?.snapshot_url, response?.url, response?.base64].filter(Boolean);
    const value = String(candidates[0] || '').trim();
    if (!value) return '';
    if (value.startsWith('data:image')) return value;
    if (value.length > 200 && !/^https?:\/\//i.test(value)) return 'data:image/jpeg;base64,' + value;
    return value;
  }

  function joinUrl(baseUrl, path) {
    baseUrl = String(baseUrl || '').replace(/\/+$/, '');
    path = '/' + String(path || '').replace(/^\/+/, '');
    if (/\/api$/i.test(baseUrl) && path.indexOf('/api/') === 0) {
      path = path.substring(4);
    }
    return baseUrl + path;
  }

  function cameraApiEndpoint(baseUrl, uuid, action) {
    uuid = String(uuid || '').trim();
    if (!baseUrl || !uuid) return '';
    return joinUrl(baseUrl, '/api/cameras/' + encodeURIComponent(uuid) + '/' + action);
  }

  function selectedFormConfigBaseUrl(form) {
    const configSelect = form ? form.querySelector('.adp-camera-config-select') : null;
    const selected = configSelect && configSelect.options ? configSelect.options[configSelect.selectedIndex] : null;
    return selected && selected.dataset ? String(selected.dataset.baseUrl || '').trim() : '';
  }

  function refreshCameraEndpointFields(form) {
    if (!form) return;
    const uuid = (form.querySelector('[name="camera_uuid"]') || {}).value || '';
    const baseUrl = selectedFormConfigBaseUrl(form);
    const stream = form.querySelector('[name="stream_url"]');
    const snapshot = form.querySelector('[name="snapshot_url"]');

    if (stream && (!stream.value || /\/api\/cameras\/[^/]+\/stream$/i.test(stream.value))) {
      stream.value = cameraApiEndpoint(baseUrl, uuid, 'stream');
    }

    if (snapshot && (!snapshot.value || /\/api\/cameras\/[^/]+\/snapshot$/i.test(snapshot.value))) {
      snapshot.value = cameraApiEndpoint(baseUrl, uuid, 'snapshot');
    }
  }

  function normalizeCameraDevice(device, runtimeConfig) {
    const cfg = device?.config || {};
    const uuid = device?.uuid || device?.id || device?.device_uuid || '';
    const baseUrl = runtimeConfig?.base_url || '';
    return {
      uuid: uuid,
      descricao: device?.name || device?.description || device?.descricao || uuid || 'Câmera ADP',
      model: device?.model || device?.modelo || '',
      driver: device?.driver || device?.driver_type || device?.device_type || '',
      protocol: device?.protocol || device?.protocolo || device?.driver_type || '',
      host: device?.host || cfg.host || '',
      port: device?.port || cfg.http_port || cfg.rtsp_port || '',
      stream_url: device?.stream_url || device?.proxy_url || device?.mjpeg_url || cfg.proxy_url || cfg.mjpeg_url || cfg.rtsp_url || cameraApiEndpoint(baseUrl, uuid, 'stream'),
      snapshot_url: device?.snapshot_url || cfg.snapshot_url || cameraApiEndpoint(baseUrl, uuid, 'snapshot'),
      proxy_url: device?.proxy_url || device?.stream_proxy_url || cfg.proxy_url || '',
      mjpeg_url: device?.mjpeg_url || cfg.mjpeg_url || '',
      rtsp_url: device?.internal_rtsp_url || device?.rtsp_url || cfg.rtsp_url || '',
      supports_stream: device?.supports_stream !== false,
      supports_snapshot: device?.supports_snapshot !== false,
      status: device?.status || (device?.enabled === false ? 'offline' : 'online'),
      raw: device || {},
    };
  }

  function extractCameraList(response, runtimeConfig) {
    const map = function (item) { return normalizeCameraDevice(item, runtimeConfig); };
    const lists = [response?.data, response?.cameras, response?.devices, response?.result, response];
    for (const item of lists) {
      if (Array.isArray(item)) return item.map(map).filter(cam => cam.uuid);
      if (item && Array.isArray(item.cameras)) return item.cameras.map(map).filter(cam => cam.uuid);
      if (item && Array.isArray(item.devices)) return item.devices.map(map).filter(cam => cam.uuid);
    }
    return [];
  }

  async function loadCamerasIntoForm(form) {
    const configSelect = form.querySelector('.adp-camera-config-select');
    const discoverySelect = form.querySelector('.adp-camera-discovery-select');
    if (!configSelect || !discoverySelect || !window.AdpRuntimeClient) return;

    const configId = configSelect.value;
    if (!configId) {
      alert('Selecione a configuração ADP antes de listar as câmeras.');
      return;
    }

    discoverySelect.style.display = '';
    discoverySelect.innerHTML = '<option value="">Carregando câmeras do ADP...</option>';

    try {
      const cfg = await window.AdpRuntimeClient.getRuntimeConfig(configId);
      const response = await window.AdpRuntimeClient.adpRequest(cfg, '/api/cameras', 'GET');
      const cameras = extractCameraList(response, cfg);
      discoverySelect.innerHTML = '<option value="">Selecione uma câmera encontrada...</option>';
      cameras.forEach(function (camera) {
        const option = document.createElement('option');
        option.value = camera.uuid;
        option.textContent = camera.descricao + ' - ' + camera.uuid;
        option.dataset.camera = JSON.stringify(camera);
        discoverySelect.appendChild(option);
      });
      if (!cameras.length) {
        discoverySelect.innerHTML = '<option value="">Nenhuma câmera retornada pelo ADP</option>';
      }
    } catch (e) {
      discoverySelect.innerHTML = '<option value="">Erro ao listar câmeras do ADP</option>';
      alert(e.message || 'Erro ao listar câmeras do ADP.');
    }
  }

  function fillFormWithCamera(form, camera) {
    const set = (name, value) => { const el = form.querySelector('[name="' + name + '"]'); if (el) el.value = value || ''; };
    const check = (name, value) => { const el = form.querySelector('[name="' + name + '"]'); if (el) el.checked = !!value; };
    set('camera_uuid', camera.uuid);
    set('descricao', camera.descricao);
    set('model', camera.model);
    set('driver', camera.driver);
    set('protocol', camera.protocol);
    set('host', camera.host);
    set('port', camera.port);
    set('stream_url', camera.stream_url);
    set('snapshot_url', camera.snapshot_url);
    set('status', camera.status);
    refreshCameraEndpointFields(form);
    check('supports_stream', camera.supports_stream);
    check('supports_snapshot', camera.supports_snapshot);
  }

  function findUrlDeep(value) {
    if (!value) return '';
    if (typeof value === 'string') {
      return /^https?:\/\//i.test(value) || /^rtsp:\/\//i.test(value) ? value : '';
    }
    if (Array.isArray(value)) {
      for (const item of value) {
        const found = findUrlDeep(item);
        if (found) return found;
      }
      return '';
    }
    if (typeof value === 'object') {
      const preferred = ['stream_url', 'url', 'rtsp_url', 'hls_url', 'webrtc_url', 'mjpeg_url', 'public_url', 'snapshot_url'];
      for (const key of preferred) {
        const found = findUrlDeep(value[key]);
        if (found) return found;
      }
      for (const key of Object.keys(value)) {
        const found = findUrlDeep(value[key]);
        if (found) return found;
      }
    }
    return '';
  }

  async function configFromRow(row) {
    const configId = row.dataset.integradorConfigId || '';
    if (configId) {
      return window.AdpRuntimeClient.getRuntimeConfig(configId);
    }

    return window.AdpRuntimeClient.getRuntimeConfigByBaseUrl(row.dataset.baseUrl || '');
  }

  async function requestCamera(row, path, method, payload) {
    const cameraUuid = row.dataset.cameraUuid;
    const cfg = await configFromRow(row);
    return window.AdpRuntimeClient.adpRequest(cfg, '/api/cameras/' + encodeURIComponent(cameraUuid) + path, method || 'GET', payload);
  }

  async function health(row) {
    const badge = document.querySelector('[data-camera-status="' + row.dataset.cameraRow + '"]');
    rowLog(row, 'Testando câmera...');
    try {
      const res = await requestCamera(row, '/health', 'GET');
      const ok = res.success !== false && res.error !== true && (res.result?.success !== false);
      setBadge(badge, ok ? 'online' : 'warning', ok ? 'Online' : 'Atenção');
      rowLog(row, ok ? 'Câmera online.' : 'Câmera respondeu com atenção.');
    } catch (e) {
      setBadge(badge, 'offline', 'Offline');
      rowLog(row, 'Falha ao testar câmera: ' + (e.message || e));
    }
  }

  async function live(row) {
    const id = row.dataset.cameraRow;
    const badge = document.querySelector('[data-camera-status="' + id + '"]');
    rowLog(row, 'Iniciando preview ao vivo...');

    try {
      const cameraUuid = row.dataset.cameraUuid;
      const cfg = await configFromRow(row);
      let resolved = null;

      if (window.AdpRuntimeClient && typeof window.AdpRuntimeClient.resolveCameraStream === 'function') {
        resolved = await window.AdpRuntimeClient.resolveCameraStream(cfg, cameraUuid);
      }

      const preferred = resolved && resolved.preferred ? resolved.preferred : null;

      if (preferred && preferred.type !== 'rtsp' && preferred.browser_url) {
        setCameraLivePreview(id, preferred.browser_url, preferred.type || 'proxy');
        setBadge(badge, 'online', 'Online');
        rowLog(row, 'Preview ao vivo iniciado via ' + String(preferred.type || 'proxy').toUpperCase() + '.');
        return;
      }

      if (preferred && preferred.type === 'rtsp' && preferred.url) {
        setBadge(badge, 'warning', 'RTSP');
        rowLog(row, 'ADP retornou RTSP direto. Navegador não renderiza RTSP nativo; abrindo URL técnica em nova aba/aplicativo.' );
        window.open(preferred.url, '_blank', 'noopener');
        return;
      }

      // Fallback: tenta snapshot caso o proxy/MJPEG ainda não esteja pronto.
      rowLog(row, 'Preview ao vivo indisponível. Tentando snapshot como fallback...');
      await snapshot(row);
    } catch (e) {
      setBadge(badge, 'offline', 'Erro');
      rowLog(row, 'Falha no preview ao vivo: ' + (e.message || e));
    }
  }

  async function stream(row) {
    // O botão Stream passa a usar a visualização ao vivo. A preferência é o
    // proxy interno do ADP, depois MJPEG, depois RTSP técnico quando não houver
    // rota visual direta para navegador.
    return live(row);
  }


  async function resolveSnapshotPreview(response) {
    let src = imageFromSnapshot(response);
    if (src && !/^([a-zA-Z]:[\\/]|\\\\)/.test(src)) return src;

    if (window.AdpRuntimeClient && typeof window.AdpRuntimeClient.resolveSnapshotPreviewViaLaravel === 'function') {
      const resolved = await window.AdpRuntimeClient.resolveSnapshotPreviewViaLaravel(response);
      if (resolved) return resolved;
    }

    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const payload = {
        response: response,
        file_path: response?.snapshot?.file_path || response?.response?.snapshot?.file_path || response?.result?.snapshot?.file_path || response?.data?.snapshot?.file_path || response?.file_path || '',
        image_url: response?.snapshot?.image_url || response?.image_url || response?.snapshot_url || '',
        image_data_url: response?.image_data_url || response?.snapshot?.image_data_url || response?.snapshot?.base64 || response?.base64 || '',
      };
      const res = await fetch('/adp/cameras/snapshot-preview', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify(payload),
      });
      const json = await res.json().catch(() => ({}));
      if (res.ok && json.success) {
        return json.image_data_url || json.image_url || '';
      }
    } catch (e) {}

    return '';
  }

  async function snapshot(row) {
    const id = row.dataset.cameraRow;
    const img = document.querySelector('[data-camera-preview="' + id + '"]');
    const empty = document.querySelector('[data-camera-preview-empty="' + id + '"]');
    const badge = document.querySelector('[data-camera-status="' + id + '"]');
    rowLog(row, 'Capturando snapshot...');
    try {
      const cameraUuid = row.dataset.cameraUuid;
      const cfg = await configFromRow(row);
      let res;
      let src = '';
      if (window.AdpRuntimeClient && typeof window.AdpRuntimeClient.snapshotCamera === 'function') {
        res = await window.AdpRuntimeClient.snapshotCamera(cfg, cameraUuid);
        if (res && res.success === false) {
          throw new Error(res.message || 'Falha ao capturar snapshot.');
        }
        src = res.image_data_url || res.image_src || res.image_url || '';
      } else {
        res = await requestCamera(row, '/snapshot', 'POST', { return_base64: true, include_base64: true, return_data_url: true });
        src = await resolveSnapshotPreview(res);
      }

      if (!src) {
        src = await resolveSnapshotPreview(res.response || res);
      }

      stopCameraLivePreview(id);
      if (!setCameraPreview(id, src) && empty) {
        empty.textContent = 'Snapshot sem imagem';
        empty.style.display = '';
      }
      setBadge(badge, 'online', src ? 'Online' : 'Sem imagem');
      rowLog(row, src ? 'Snapshot capturado com imagem.' : 'Snapshot executado, mas sem imagem retornada.');
    } catch (e) {
      setBadge(badge, 'offline', 'Erro');
      rowLog(row, 'Falha no snapshot: ' + (e.message || e));
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-camera-row]').forEach(function (row) {
      const id = row.dataset.cameraRow;
      const btnTest = document.querySelector('[data-camera-test="' + id + '"]');
      const btnStream = document.querySelector('[data-camera-stream="' + id + '"]');
      const btnLive = document.querySelector('[data-camera-live="' + id + '"]');
      const btnSnapshot = document.querySelector('[data-camera-snapshot="' + id + '"]');

      btnTest && btnTest.addEventListener('click', function () { health(row); });
      btnStream && btnStream.addEventListener('click', function () { stream(row); });
      btnLive && btnLive.addEventListener('click', function () { live(row); });
      btnSnapshot && btnSnapshot.addEventListener('click', function () { snapshot(row); });
    });

    document.querySelectorAll('.adp-camera-access-mode').forEach(function (select) {
      const form = select.closest('form');
      const box = form ? form.querySelector('.adp-camera-users-box') : null;
      const apply = () => { if (box) box.style.display = select.value === 'selective' ? '' : 'none'; };
      select.addEventListener('change', apply);
      apply();
    });

    document.querySelectorAll('.adp-camera-load-from-api').forEach(function (button) {
      button.addEventListener('click', function () {
        const form = button.closest('form');
        if (form) loadCamerasIntoForm(form);
      });
    });

    document.querySelectorAll('.adp-camera-discovery-select').forEach(function (select) {
      select.addEventListener('change', function () {
        if (!select.value) return;
        const selected = select.options[select.selectedIndex];
        try {
          const camera = JSON.parse(selected.dataset.camera || '{}');
          const form = select.closest('form');
          if (form && camera.uuid) fillFormWithCamera(form, camera);
        } catch (e) {}
      });
    });

    document.querySelectorAll('.adp-camera-config-select').forEach(function (select) {
      select.addEventListener('change', function () { refreshCameraEndpointFields(select.closest('form')); });
      refreshCameraEndpointFields(select.closest('form'));
    });

    document.querySelectorAll('[name="camera_uuid"]').forEach(function (input) {
      input.addEventListener('input', function () { refreshCameraEndpointFields(input.closest('form')); });
      input.addEventListener('change', function () { refreshCameraEndpointFields(input.closest('form')); });
    });
  });
})();
