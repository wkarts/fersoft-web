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

  async function getRuntimeConfig(integradorConfigId) {
    const id = String(integradorConfigId || '').trim();
    if (!id) {
      throw new Error('Configuração ADP não informada.');
    }

    if (runtimeCache.has(id)) {
      return runtimeCache.get(id);
    }

    const json = await fetchJson('/adp/discovery/configs/runtime?integrador_config_id=' + encodeURIComponent(id), {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });

    if (!json.success || !json.config) {
      throw new Error(json.message || 'Não foi possível obter a configuração ADP.');
    }

    runtimeCache.set(id, json.config);
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

    if (balanca.integrador_config_id) {
      return getRuntimeConfig(balanca.integrador_config_id);
    }

    return getRuntimeConfigByBaseUrl(balanca.backend || balanca.backend_server_address || balanca.base_url || '');
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

  async function snapshotCamera(config, uuid) {
    try {
      const result = await adpRequest(config, '/api/cameras/' + encodeURIComponent(uuid) + '/snapshot', 'POST', {});
      return {
        uuid,
        success: dataGet(result, ['success', 'result.success'], true) !== false,
        response: result,
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
  };
})(window);
