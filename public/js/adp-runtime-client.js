(function (window) {
  'use strict';

  const runtimeCache = new Map();

  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  function cleanBaseUrl(baseUrl) {
    return String(baseUrl || '').trim().replace(/\/+$/, '');
  }

  function apiUrl(config, path) {
    const base = cleanBaseUrl(config.base_url || config.baseUrl || '');
    const cleanPath = String(path || '').startsWith('/') ? String(path || '') : '/' + String(path || '');

    if (!base) {
      throw new Error('Base URL ADP não configurada.');
    }

    if (base.endsWith('/api') && cleanPath.startsWith('/api/')) {
      return base + cleanPath.substring(4);
    }

    return base + cleanPath;
  }

  function authHeaders(config) {
    const headers = {
      'Accept': 'application/json',
    };

    const enabled = config.global_token_enabled !== false;
    const token = config.global_token || config.token || null;
    const type = config.global_token_type || config.token_type || 'x_adp_api_token';
    const header = config.global_token_header || config.token_header || 'X-ADP-API-TOKEN';

    if (!enabled || !token || type === 'none' || type === 'query') {
      return headers;
    }

    if (type === 'bearer') {
      headers.Authorization = 'Bearer ' + token;
      return headers;
    }

    headers[header] = token;
    return headers;
  }

  function withQueryToken(url, config) {
    const token = config.global_token || config.token || null;
    const type = config.global_token_type || config.token_type || 'x_adp_api_token';

    if (!token || type !== 'query') {
      return url;
    }

    const parsed = new URL(url, window.location.href);
    parsed.searchParams.set('token', token);
    return parsed.toString();
  }

  async function fetchJson(url, options) {
    const response = await fetch(url, options || {});
    const text = await response.text();
    let json = null;

    try {
      json = text ? JSON.parse(text) : {};
    } catch (e) {
      json = { success: false, message: text || 'Resposta inválida.' };
    }

    if (!response.ok) {
      const message = json.message || json.error || `Falha HTTP ${response.status}`;
      const error = new Error(message);
      error.status = response.status;
      error.response = json;
      throw error;
    }

    return json;
  }

  async function getRuntimeConfig(integradorConfigId, baseUrl) {
    const id = String(integradorConfigId || '').trim();
    const base = cleanBaseUrl(baseUrl || '');

    if (!id && !base) {
      throw new Error('Configuração ADP não informada.');
    }

    const cacheKey = id ? `id:${id}|base:${base}` : `base:${base}`;
    if (runtimeCache.has(cacheKey)) {
      return runtimeCache.get(cacheKey);
    }

    const params = new URLSearchParams();
    if (id) params.set('integrador_config_id', id);
    if (base) params.set('base_url', base);

    const json = await fetchJson('/adp/discovery/configs/runtime?' + params.toString(), {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });

    if (!json.success || !json.config) {
      throw new Error(json.message || 'Não foi possível obter a configuração ADP.');
    }

    runtimeCache.set(cacheKey, json.config);
    if (json.config.id) {
      runtimeCache.set(`id:${json.config.id}|base:${cleanBaseUrl(json.config.base_url || '')}`, json.config);
    }
    return json.config;
  }

  async function adpRequest(config, path, method, payload) {
    let url = withQueryToken(apiUrl(config, path), config);
    const headers = authHeaders(config);
    const options = {
      method: method || 'GET',
      headers,
    };

    if (payload !== undefined && payload !== null) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(payload);
    }

    return fetchJson(url, options);
  }

  function asNumber(value, fallback) {
    const n = Number(value);
    return Number.isFinite(n) ? n : (fallback || 0);
  }

  function dataGet(obj, paths, fallback) {
    for (const path of paths) {
      const parts = String(path).split('.');
      let cur = obj;
      let ok = true;
      for (const part of parts) {
        if (cur === undefined || cur === null || !(part in Object(cur))) {
          ok = false;
          break;
        }
        cur = cur[part];
      }
      if (ok && cur !== undefined && cur !== null && cur !== '') {
        return cur;
      }
    }
    return fallback;
  }

  function normalizeReadResponse(response, balanca) {
    const bruto = asNumber(dataGet(response, [
      'peso', 'weight', 'data.peso', 'data.weight', 'data.peso_bruto', 'data.gross_weight',
      'data.peso_liq', 'data.peso_liquido', 'current.weight', 'current.peso'
    ], 0));

    const tara = asNumber(dataGet(response, ['tara', 'data.tara', 'data.tare'], 0));
    const liquido = asNumber(dataGet(response, ['peso_liquido', 'data.peso_liquido', 'data.peso_liq', 'data.net_weight'], Math.max(0, bruto - tara)));
    const estavel = Boolean(dataGet(response, ['estavel', 'stable', 'data.estavel', 'data.stable', 'health.stable'], false));
    const sobrecarga = Boolean(dataGet(response, ['sobrecarga', 'overload', 'data.sobrecarga', 'data.overload'], false));
    const unidade = dataGet(response, ['unidade', 'unit', 'data.unidade', 'data.unit'], 'kg');
    const connected = Boolean(dataGet(response, ['health.connected', 'connected', 'success'], true));
    const receiving = Boolean(dataGet(response, ['health.receiving_data', 'receiving_data'], bruto > 0 || connected));

    return {
      success: dataGet(response, ['success'], true) !== false,
      balanca_id: balanca?.id || null,
      descricao: balanca?.descricao || '',
      peso: bruto,
      peso_bruto: bruto,
      peso_liquido: liquido,
      tara: tara,
      peso_formatado: bruto.toLocaleString('pt-BR', { maximumFractionDigits: 2 }),
      unidade: unidade,
      estavel: estavel,
      sobrecarga: sobrecarga,
      status: connected ? (receiving ? 'online' : 'sem_transmissao') : 'offline',
      raw: response,
      read_at: new Date().toISOString(),
    };
  }

  function parseUuidList(value) {
    if (Array.isArray(value)) {
      return value.map(v => String(v || '').trim()).filter(Boolean);
    }

    const raw = String(value || '').trim();
    if (!raw) {
      return [];
    }

    try {
      const decoded = JSON.parse(raw);
      if (Array.isArray(decoded)) {
        return decoded.map(v => String(v || '').trim()).filter(Boolean);
      }
      if (decoded) {
        return [String(decoded).trim()].filter(Boolean);
      }
    } catch (e) {}

    return raw.split(',').map(v => String(v || '').trim()).filter(Boolean);
  }

  function buildBalancaFromSelectOption(option) {
    if (!option || !option.value) {
      return null;
    }

    return {
      id: option.value,
      descricao: option.textContent.trim(),
      integrador: option.dataset.integrador || 'adp',
      integrador_config_id: option.dataset.integradorConfigId || '',
      adp_scale_uuid: option.dataset.adpScaleUuid || '',
      adp_camera_uuids: option.dataset.adpCameraUuids || '[]',
      modelo: option.dataset.modelo || '',
      porta: option.dataset.port || '',
      backend: option.dataset.backend || '',
    };
  }


  async function getRuntimeConfigByBaseUrl(baseUrl) {
    const target = cleanBaseUrl(baseUrl);
    if (!target) {
      throw new Error('Base URL ADP não informada para localizar configuração global.');
    }

    const json = await fetchJson('/adp/discovery/configs', {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });

    const configs = Array.isArray(json.configs) ? json.configs : [];
    let found = configs.find(cfg => cleanBaseUrl(cfg.base_url) === target);

    if (!found && configs.length === 1) {
      found = configs[0];
    }

    if (!found) {
      throw new Error('Balança ADP sem configuração global vinculada. Edite a balança e selecione a configuração ADP.');
    }

    return getRuntimeConfig(found.id);
  }

  async function configForBalanca(balanca) {
    if (!balanca) {
      throw new Error('Balança ADP não informada.');
    }

    const base = balanca.backend || balanca.backend_server_address || balanca.base_url || '';

    // Regra crítica: quando a balança possui integrador_config_id, esse ID é a
    // fonte da verdade. Não fazer fallback silencioso para outro ADP por Base URL,
    // pois empresas podem ter vários servidores ADP com tokens diferentes.
    if (balanca.integrador_config_id) {
      return await getRuntimeConfig(balanca.integrador_config_id, base);
    }

    return getRuntimeConfigByBaseUrl(base);
  }

  async function openScale(balanca) {
    const config = await configForBalanca(balanca);
    return adpRequest(config, '/api/scales/' + encodeURIComponent(balanca.adp_scale_uuid) + '/open', 'POST', {});
  }

  async function closeScale(balanca) {
    const config = await configForBalanca(balanca);
    return adpRequest(config, '/api/scales/' + encodeURIComponent(balanca.adp_scale_uuid) + '/close', 'POST', {});
  }

  async function readScale(balanca) {
    const config = await configForBalanca(balanca);
    const raw = await adpRequest(config, '/api/scales/' + encodeURIComponent(balanca.adp_scale_uuid) + '/data', 'GET');
    return normalizeReadResponse(raw, balanca);
  }

  async function healthScale(balanca) {
    const config = await configForBalanca(balanca);
    return adpRequest(config, '/api/scales/' + encodeURIComponent(balanca.adp_scale_uuid) + '/health', 'GET');
  }


  function collectImageCandidates(value) {
    const candidates = [];
    const preferredKeys = [
      'image_data_url', 'data_url', 'image_base64', 'base64', 'snapshot_base64',
      'file_base64', 'jpeg_base64', 'jpg_base64', 'png_base64', 'image',
      'image_url', 'snapshot_url', 'url', 'file_url', 'public_url', 'download_url',
    ];

    function walk(node) {
      if (typeof node === 'string') {
        const text = node.trim();
        if (text && (text.startsWith('data:image') || /^https?:\/\//i.test(text) || text.length > 200)) {
          candidates.push(text);
        }
        return;
      }

      if (!node || typeof node !== 'object') {
        return;
      }

      preferredKeys.forEach(function (key) {
        if (typeof node[key] === 'string' && node[key].trim()) {
          candidates.push(node[key].trim());
        }
      });

      Object.keys(node).forEach(function (key) {
        const child = node[key];
        if (child && typeof child === 'object') {
          walk(child);
        }
      });
    }

    walk(value);
    return Array.from(new Set(candidates));
  }

  function imageSrcFromSnapshotResponse(response) {
    const candidates = collectImageCandidates(response);

    const dataCandidate = candidates.find(value => value.startsWith('data:image') || (!/^https?:\/\//i.test(value) && value.length > 200));
    const urlCandidate = candidates.find(value => /^https?:\/\//i.test(value));
    const value = String(dataCandidate || urlCandidate || '').trim();

    if (!value) {
      return '';
    }

    if (value.startsWith('data:image')) {
      return value;
    }

    if (value.length > 200 && !/^https?:\/\//i.test(value)) {
      return 'data:image/jpeg;base64,' + value.replace(/^data:image\/\w+;base64,/, '');
    }

    return value;
  }

  async function imageUrlToDataUrl(src) {
    if (!src || src.startsWith('data:image')) {
      return src || '';
    }

    try {
      const response = await fetch(src, { method: 'GET', mode: 'cors' });
      if (!response.ok) {
        return '';
      }
      const blob = await response.blob();
      return await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(String(reader.result || ''));
        reader.onerror = reject;
        reader.readAsDataURL(blob);
      });
    } catch (e) {
      return '';
    }
  }

  async function blobToDataUrl(blob) {
    return await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(String(reader.result || ''));
      reader.onerror = reject;
      reader.readAsDataURL(blob);
    });
  }


  function dataGetFirst(obj, paths, fallback) {
    for (const path of paths) {
      const value = dataGet(obj, [path], null);
      if (value !== null && value !== undefined && String(value).trim() !== '') {
        return value;
      }
    }
    return fallback;
  }

  async function resolveSnapshotPreviewViaLaravel(response) {
    try {
      const payload = {
        response: response || {},
        file_path: dataGetFirst(response || {}, [
          'snapshot.file_path', 'response.snapshot.file_path', 'result.snapshot.file_path', 'data.snapshot.file_path',
          'file_path', 'path'
        ], ''),
        image_url: dataGetFirst(response || {}, [
          'snapshot.image_url', 'response.snapshot.image_url', 'result.snapshot.image_url', 'data.snapshot.image_url',
          'image_url', 'snapshot_url', 'url', 'public_url', 'download_url'
        ], ''),
        image_data_url: dataGetFirst(response || {}, [
          'image_data_url', 'data_url', 'snapshot.image_data_url', 'response.snapshot.image_data_url', 'result.snapshot.image_data_url',
          'snapshot.base64', 'response.snapshot.base64', 'result.snapshot.base64', 'image_base64', 'base64'
        ], ''),
      };

      const res = await fetch('/adp/cameras/snapshot-preview', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload),
      });

      const json = await res.json().catch(() => ({}));
      if (res.ok && json && json.success) {
        return json.image_data_url || json.image_url || '';
      }
    } catch (e) {}

    return '';
  }

  async function snapshotCameraRaw(config, uuid) {
    let url = withQueryToken(apiUrl(config, '/api/cameras/' + encodeURIComponent(uuid) + '/snapshot'), config);
    const headers = authHeaders(config);
    headers['Content-Type'] = 'application/json';
    headers['Accept'] = 'application/json, image/*';

    const response = await fetch(url, {
      method: 'POST',
      headers,
      body: JSON.stringify({
        return_base64: true,
        include_base64: true,
        return_data_url: true,
      }),
    });

    const contentType = (response.headers.get('content-type') || '').toLowerCase();

    if (!response.ok) {
      let message = 'Falha ao capturar snapshot da câmera.';
      try {
        const json = contentType.includes('application/json') ? await response.json() : null;
        message = json?.message || json?.error || message;
      } catch (e) {}
      const error = new Error(message);
      error.status = response.status;
      throw error;
    }

    if (contentType.startsWith('image/')) {
      const blob = await response.blob();
      return {
        success: true,
        mime_type: contentType.split(';')[0],
        image_data_url: await blobToDataUrl(blob),
      };
    }

    const text = await response.text();
    try {
      return text ? JSON.parse(text) : { success: true };
    } catch (e) {
      return { success: true, raw: text };
    }
  }

  async function snapshotCamera(config, uuid) {
    try {
      const result = await snapshotCameraRaw(config, uuid);
      let imageSrc = imageSrcFromSnapshotResponse(result);
      let imageDataUrl = await imageUrlToDataUrl(imageSrc);

      if (!imageDataUrl && (!imageSrc || imageSrc.indexOf('C:') === 0 || imageSrc.indexOf('\\') === 0)) {
        imageDataUrl = await resolveSnapshotPreviewViaLaravel(result);
        if (imageDataUrl) {
          imageSrc = imageDataUrl;
        }
      }

      if (!imageDataUrl && !imageSrc) {
        imageDataUrl = await resolveSnapshotPreviewViaLaravel(result);
        if (imageDataUrl) {
          imageSrc = imageDataUrl;
        }
      }

      return {
        uuid,
        success: dataGet(result, ['success', 'result.success', 'snapshot.success'], true) !== false,
        response: result,
        image_src: imageSrc,
        image_data_url: imageDataUrl || (imageSrc && imageSrc.startsWith('data:image') ? imageSrc : ''),
        image_url: imageSrc && !imageSrc.startsWith('data:image') ? imageSrc : '',
        captured_at: new Date().toISOString(),
      };
    } catch (e) {
      return {
        uuid,
        success: false,
        message: e.message,
        captured_at: new Date().toISOString(),
      };
    }
  }


  function absoluteAdpUrl(config, pathOrUrl) {
    const value = String(pathOrUrl || '').trim();

    if (!value) {
      return '';
    }

    if (/^https?:\/\//i.test(value) || /^rtsp:\/\//i.test(value)) {
      return value;
    }

    return apiUrl(config, value);
  }

  function withBrowserToken(url, config) {
    if (!url || /^rtsp:\/\//i.test(url)) {
      return url || '';
    }

    const token = config.global_token || config.token || null;
    const enabled = config.global_token_enabled !== false;

    if (!enabled || !token) {
      return url;
    }

    try {
      const parsed = new URL(url, window.location.href);

      // A documentação nova do ADP aceita token por header e também por query
      // `token`/`api_token` nos endpoints de câmera. Para <img> MJPEG/proxy não
      // é possível enviar header, então o preview ao vivo usa query string.
      if (!parsed.searchParams.get('token')) {
        parsed.searchParams.set('token', token);
      }
      if (!parsed.searchParams.get('api_token')) {
        parsed.searchParams.set('api_token', token);
      }

      return parsed.toString();
    } catch (e) {
      return url;
    }
  }

  function collectStreamUrlCandidates(response) {
    const candidates = [];
    const preferredKeys = [
      'proxy_url', 'stream_proxy_url', 'mjpeg_proxy_url',
      'mjpeg_url', 'stream_mjpeg_url', 'native_mjpeg_url',
      'stream_url', 'public_stream_url', 'url',
      'internal_rtsp_url', 'rtsp_url', 'source_url'
    ];

    function push(value, type, priority) {
      const url = String(value || '').trim();
      if (!url) return;
      candidates.push({ url, type, priority });
    }

    function classify(key, value) {
      const lowerKey = String(key || '').toLowerCase();
      const lowerValue = String(value || '').toLowerCase();

      if (lowerKey.includes('proxy') || lowerValue.includes('/stream/proxy')) {
        push(value, 'proxy', 10);
        return;
      }

      if (lowerKey.includes('mjpeg') || lowerValue.includes('/stream/mjpeg') || lowerValue.includes('multipart')) {
        push(value, 'mjpeg', 20);
        return;
      }

      if (lowerValue.startsWith('rtsp://') || lowerKey.includes('rtsp')) {
        push(value, 'rtsp', 40);
        return;
      }

      if (lowerKey.includes('snapshot')) {
        push(value, 'snapshot', 50);
        return;
      }

      push(value, 'http', 30);
    }

    function walk(node) {
      if (!node) return;

      if (typeof node === 'string') {
        if (/^(https?|rtsp):\/\//i.test(node) || node.indexOf('/api/cameras/') >= 0) {
          classify('', node);
        }
        return;
      }

      if (Array.isArray(node)) {
        node.forEach(walk);
        return;
      }

      if (typeof node === 'object') {
        preferredKeys.forEach(function (key) {
          if (typeof node[key] === 'string' && node[key].trim()) {
            classify(key, node[key]);
          }
        });

        Object.keys(node).forEach(function (key) {
          if (preferredKeys.indexOf(key) < 0) {
            walk(node[key]);
          }
        });
      }
    }

    walk(response || {});

    const unique = [];
    const seen = new Set();
    candidates
      .sort((a, b) => a.priority - b.priority)
      .forEach(function (item) {
        const key = item.type + '|' + item.url;
        if (!seen.has(key)) {
          seen.add(key);
          unique.push(item);
        }
      });

    return unique;
  }

  function defaultCameraStreamCandidates(config, uuid) {
    const encoded = encodeURIComponent(uuid);
    return [
      { type: 'proxy', priority: 10, url: apiUrl(config, '/api/cameras/' + encoded + '/stream/proxy') },
      { type: 'mjpeg', priority: 20, url: apiUrl(config, '/api/cameras/' + encoded + '/stream/mjpeg') },
      { type: 'stream', priority: 30, url: apiUrl(config, '/api/cameras/' + encoded + '/stream') },
      { type: 'snapshot', priority: 50, url: apiUrl(config, '/api/cameras/' + encoded + '/snapshot') }
    ];
  }

  function normalizeStreamCandidate(config, item) {
    const type = item.type || 'http';
    const absolute = absoluteAdpUrl(config, item.url);

    return {
      type,
      url: absolute,
      browser_url: type === 'rtsp' ? absolute : withBrowserToken(absolute, config),
      priority: item.priority || 99
    };
  }

  async function resolveCameraStream(config, uuid) {
    if (!config) {
      throw new Error('Configuração ADP não informada.');
    }

    if (!uuid) {
      throw new Error('UUID da câmera não informado.');
    }

    let response = null;
    let candidates = [];

    try {
      response = await adpRequest(config, '/api/cameras/' + encodeURIComponent(uuid) + '/stream', 'GET');
      candidates = collectStreamUrlCandidates(response);
    } catch (e) {
      response = { success: false, message: e.message };
    }

    const defaults = defaultCameraStreamCandidates(config, uuid);
    const all = candidates.concat(defaults)
      .map(function (item) { return normalizeStreamCandidate(config, item); })
      .filter(function (item) { return item.url; });

    const seen = new Set();
    const unique = all.filter(function (item) {
      const key = item.type + '|' + item.url;
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    }).sort(function (a, b) { return a.priority - b.priority; });

    const preferred = unique.find(function (item) { return item.type === 'proxy'; })
      || unique.find(function (item) { return item.type === 'mjpeg'; })
      || unique.find(function (item) { return item.type !== 'rtsp'; })
      || unique[0]
      || null;

    return {
      success: Boolean(preferred),
      response,
      preferred,
      candidates: unique,
      proxy_url: (unique.find(function (item) { return item.type === 'proxy'; }) || {}).browser_url || '',
      mjpeg_url: (unique.find(function (item) { return item.type === 'mjpeg'; }) || {}).browser_url || '',
      rtsp_url: (unique.find(function (item) { return item.type === 'rtsp'; }) || {}).url || '',
    };
  }

  async function captureEvidence(balanca) {
    const config = await configForBalanca(balanca);
    const read = await readScale(balanca);
    const cameraUuids = parseUuidList(balanca.adp_camera_uuids);
    const snapshots = [];

    for (const uuid of cameraUuids) {
      snapshots.push(await snapshotCamera(config, uuid));
    }

    return {
      success: Boolean(read.success),
      event_id: 'ADP-' + Date.now(),
      balanca: {
        id: Number(balanca.id || 0),
        descricao: balanca.descricao || '',
        modelo: balanca.modelo || 'ADP',
        driver: 'adp',
        adp_scale_uuid: balanca.adp_scale_uuid || '',
      },
      peso: {
        valor: read.peso,
        bruto: read.peso_bruto,
        liquido: read.peso_liquido,
        tara: read.tara,
        formatado: read.peso_formatado,
        unidade: read.unidade,
        estavel: read.estavel,
        origem: 'balanca',
        capturado_em: read.read_at,
      },
      cameras: snapshots,
      metadata: {
        integrador: 'adp',
        integrador_config_id: balanca.integrador_config_id || null,
        origem: 'browser',
      },
      raw_read: read.raw || null,
    };
  }

  window.AdpRuntimeClient = {
    getRuntimeConfig,
    getRuntimeConfigByBaseUrl,
    adpRequest,
    readScale,
    healthScale,
    openScale,
    closeScale,
    captureEvidence,
    parseUuidList,
    buildBalancaFromSelectOption,
    normalizeReadResponse,
    imageSrcFromSnapshotResponse,
    snapshotCameraRaw,
    snapshotCamera,
    resolveSnapshotPreviewViaLaravel,
    resolveCameraStream,
    withBrowserToken,
  };
})(window);
