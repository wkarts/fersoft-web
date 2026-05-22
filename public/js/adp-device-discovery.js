(function () {
  'use strict';

  var initializedGlobalEvents = false;

  function parseJsonSafe(text, fallbackMessage) {
    if (!text) {
      return { success: false, message: fallbackMessage || 'Resposta vazia do servidor.' };
    }

    try {
      return JSON.parse(text);
    } catch (e) {
      return { success: false, message: fallbackMessage || 'Resposta inválida do servidor.', raw_response: text };
    }
  }

  function normalizeLocalUrl(url) {
    return new URL(url, window.location.origin).toString();
  }

  function xhrJson(url, opts) {
    opts = opts || {};

    return new Promise(function (resolve) {
      var xhr = new XMLHttpRequest();
      xhr.open(opts.method || 'GET', normalizeLocalUrl(url), true);
      xhr.withCredentials = true;
      xhr.setRequestHeader('Accept', 'application/json');

      var headers = opts.headers || {};
      Object.keys(headers).forEach(function (key) {
        if (headers[key] !== undefined && headers[key] !== null && headers[key] !== '') {
          xhr.setRequestHeader(key, headers[key]);
        }
      });

      xhr.onload = function () {
        var json = parseJsonSafe(xhr.responseText, 'Resposta inválida do servidor.');
        if (!xhr.status || xhr.status < 200 || xhr.status >= 300) {
          json.success = false;
          json.message = json.message || ('Falha HTTP ' + xhr.status);
        }
        json.http_status = xhr.status;
        resolve(json);
      };

      xhr.onerror = function () {
        resolve({ success: false, message: 'Falha de rede ao comunicar com o ERP.', http_status: 0 });
      };

      xhr.ontimeout = function () {
        resolve({ success: false, message: 'Tempo esgotado ao comunicar com o ERP.', http_status: 0 });
      };

      xhr.timeout = opts.timeout || 30000;
      xhr.send(opts.body || null);
    });
  }

  function getJson(url, opts) {
    opts = opts || {};
    var finalUrl = normalizeLocalUrl(url);
    var fetchOpts = Object.assign({
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Accept': 'application/json' }
    }, opts || {});

    fetchOpts.headers = Object.assign({ 'Accept': 'application/json' }, opts.headers || {});

    if (window.fetch) {
      return fetch(finalUrl, fetchOpts).then(function (r) {
        return r.text().then(function (text) {
          var json = parseJsonSafe(text, 'Resposta inválida do servidor.');
          if (!r.ok) {
            json.success = false;
            json.message = json.message || ('Falha HTTP ' + r.status);
          }
          json.http_status = r.status;
          return json;
        });
      }).catch(function () {
        return xhrJson(finalUrl, opts);
      });
    }

    return xhrJson(finalUrl, opts);
  }

  function q(scope, key) {
    return scope ? scope.querySelector('[data-adp="' + key + '"]') : null;
  }

  function qa(scope, key) {
    return scope ? Array.from(scope.querySelectorAll('[data-adp="' + key + '"]')) : [];
  }

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function errorText(res, fallback) {
    if (!res) return fallback || 'Erro desconhecido.';
    var parts = [];
    if (res.message) parts.push(res.message);
    if (Array.isArray(res.errors) && res.errors.length) parts.push(res.errors.join(' | '));
    if (res.status_code || res.http_status) parts.push('HTTP ' + (res.status_code || res.http_status));
    return parts.filter(Boolean).join(' | ') || fallback || 'Falha na operação.';
  }

  function updateLog(scope, text) {
    var log = q(scope, 'log');
    if (!log) return;
    var line = '[' + new Date().toLocaleTimeString('pt-BR') + '] ' + text;
    if ('value' in log) {
      log.value = line + "\n" + (log.value || '');
    } else {
      log.textContent = line + "\n" + (log.textContent || '');
    }
  }

  function joinUrl(baseUrl, path) {
    baseUrl = String(baseUrl || '').replace(/\/+$/, '');
    path = '/' + String(path || '').replace(/^\/+/, '');
    if (/\/api$/i.test(baseUrl) && path.indexOf('/api/') === 0) {
      path = path.substring(4);
    }
    return baseUrl + path;
  }

  function optionByValue(select, value) {
    if (!select || value === undefined || value === null || String(value) === '') return null;
    value = String(value);
    return Array.from(select.options || []).find(function (opt) {
      return String(opt.value || '') === value;
    }) || null;
  }

  function configSelect(scope) {
    return q(scope, 'integrador-config-id');
  }

  function selectedConfigOption(scope) {
    var select = configSelect(scope);
    if (!select) return null;
    return optionByValue(select, select.value) || select.options[select.selectedIndex] || null;
  }

  function selectedConfigId(scope) {
    var select = configSelect(scope);
    return select ? String(select.value || '').trim() : '';
  }

  function setValue(el, value) {
    if (!el) return;
    el.value = value === undefined || value === null ? '' : String(value);
  }

  function updateTokenHint(scope, option) {
    var hint = q(scope, 'config-token-mask');
    if (!hint) return;

    var mask = option && option.dataset ? option.dataset.tokenMasked : '';
    hint.textContent = mask ? ('Token global mascarado: ' + mask) : '';
  }

  function applySelectedConfig(scope, options) {
    options = options || {};
    var select = configSelect(scope);
    if (!select) return null;

    var option = selectedConfigOption(scope);

    if (!option || !option.value) {
      if (options.clear !== false) {
        setValue(q(scope, 'cfg-descricao'), '');
        setValue(q(scope, 'cfg-base-url'), '');
        setValue(q(scope, 'cfg-global-token'), '');
        setValue(q(scope, 'backend-server-address'), '');
        setValue(q(scope, 'integrador-config-id-hidden'), '');
      }
      updateTokenHint(scope, null);
      return null;
    }

    var data = option.dataset || {};
    var cfg = {
      id: String(option.value || '').trim(),
      descricao: data.descricao || '',
      baseUrl: String(data.baseUrl || '').replace(/\/+$/, ''),
      tokenType: data.tokenType || 'x_adp_api_token',
      tokenHeader: data.tokenHeader || 'X-ADP-API-TOKEN',
      tokenMasked: data.tokenMasked || '',
      timeoutMs: parseInt(data.timeoutMs || '5000', 10) || 5000
    };

    setValue(q(scope, 'cfg-descricao'), cfg.descricao);
    setValue(q(scope, 'cfg-base-url'), cfg.baseUrl);
    setValue(q(scope, 'cfg-token-type'), cfg.tokenType);
    setValue(q(scope, 'backend-server-address'), cfg.baseUrl);
    setValue(q(scope, 'integrador-config-id-hidden'), cfg.id);
    updateTokenHint(scope, option);

    return cfg;
  }

  function ensureSelectedFromServer(scope) {
    var select = configSelect(scope);
    if (!select) return;

    var current = String(select.dataset.currentValue || scope.dataset.serverIntegradorConfigId || scope.dataset.defaultIntegradorConfigId || select.value || '').trim();

    if (current && optionByValue(select, current)) {
      select.value = current;
    }

    applySelectedConfig(scope, { clear: false });
  }

  function currentConfig(scope) {
    var cfg = applySelectedConfig(scope, { clear: false });
    if (cfg) return cfg;

    var baseUrl = String((q(scope, 'cfg-base-url') || {}).value || '').trim().replace(/\/+$/, '');
    var token = String((q(scope, 'cfg-global-token') || {}).value || '').trim();
    var tokenType = String((q(scope, 'cfg-token-type') || {}).value || 'x_adp_api_token').trim();

    if (!baseUrl) return null;

    return {
      id: null,
      descricao: String((q(scope, 'cfg-descricao') || {}).value || '').trim(),
      baseUrl: baseUrl,
      tokenType: tokenType,
      tokenHeader: tokenType === 'bearer' ? 'Authorization' : 'X-ADP-API-TOKEN',
      token: token,
      timeoutMs: 5000
    };
  }

  function runtimeConfig(scope) {
    var cfg = currentConfig(scope);

    if (!cfg) {
      return Promise.resolve({ success: false, message: 'Selecione uma configuração ADP ou informe a Base URL.' });
    }

    if (!cfg.id) {
      return Promise.resolve({
        success: true,
        config: {
          id: null,
          base_url: cfg.baseUrl,
          global_token: cfg.token || '',
          global_token_type: cfg.tokenType || 'x_adp_api_token',
          global_token_header: cfg.tokenHeader || 'X-ADP-API-TOKEN',
          timeout_ms: cfg.timeoutMs || 5000
        }
      });
    }

    return getJson('/adp/discovery/configs/runtime?integrador_config_id=' + encodeURIComponent(cfg.id)).then(function (res) {
      if (!res.success) return res;
      res.config = res.config || {};

      var typedToken = String((q(scope, 'cfg-global-token') || {}).value || '').trim();
      if (typedToken) {
        res.config.global_token = typedToken;
      }

      return res;
    });
  }

  function localHeaders(config) {
    var headers = { 'Accept': 'application/json' };
    var type = config.global_token_type || 'x_adp_api_token';
    var token = config.global_token || '';
    var header = config.global_token_header || 'X-ADP-API-TOKEN';

    if (!token || type === 'none') return headers;
    if (type === 'bearer') headers.Authorization = 'Bearer ' + token;
    else if (type === 'x_adp_api_token') headers[header || 'X-ADP-API-TOKEN'] = token;
    return headers;
  }

  function localGet(config, path) {
    var url = joinUrl(config.base_url, path);
    var type = config.global_token_type || 'x_adp_api_token';
    var token = config.global_token || '';

    if (type === 'query' && token) {
      url += (url.indexOf('?') >= 0 ? '&' : '?') + 'token=' + encodeURIComponent(token);
    }

    return fetch(url, {
      method: 'GET',
      mode: 'cors',
      cache: 'no-store',
      headers: localHeaders(config)
    }).then(function (r) {
      return r.text().then(function (text) {
        var json = parseJsonSafe(text, 'Resposta inválida do ADP local.');
        if (Array.isArray(json)) {
          json = { success: r.ok, data: json };
        }
        if (r.ok && json && typeof json === 'object' && !Object.prototype.hasOwnProperty.call(json, 'success')) {
          json.success = true;
        }
        if (!r.ok) {
          json.success = false;
          json.message = json.message || ('Falha HTTP ' + r.status + ' no ADP local.');
        }
        json.http_status = r.status;
        return json;
      });
    }).catch(function (err) {
      return {
        success: false,
        message: 'Não foi possível acessar a API ADP pelo navegador. Verifique se o serviço ADP está ativo, URL/porta, CORS e token.',
        error: err.message
      };
    });
  }

  function extractList(response, keys) {
    if (Array.isArray(response)) return response;
    response = response || {};
    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];
      if (Array.isArray(response[key])) return response[key];
      if (response.data && Array.isArray(response.data[key])) return response.data[key];
    }
    if (Array.isArray(response.data)) return response.data;
    return [];
  }

  function normalizeDevice(device, forcedType) {
    device = device || {};
    var config = device.config || {};
    var driver = String(device.driver || device.driver_name || config.driver || '').toLowerCase();
    var type = forcedType || device.type || (driver.indexOf('camera') >= 0 ? 'camera' : 'scale');
    var uuid = device.uuid || device.id || device.device_uuid || device.code || device.api_token || device.token || config.uuid || config.id || '';

    return {
      uuid: String(uuid || ''),
      type: type,
      name: String(device.name || device.descricao || device.description || config.name || config.description || (type === 'camera' ? 'Câmera ADP' : 'Balança ADP')),
      model: device.model || device.modelo || config.model || config.modelo || '',
      driver: device.driver || device.driver_name || config.driver || '',
      protocol: device.protocol || device.protocolo || config.protocol || '',
      host: device.host || device.ip || config.host || config.ip || '',
      port: device.port || device.porta || device.porta_serial || config.port || config.porta || '',
      baud_rate: device.baud_rate || device.baudRate || device.velocidade || config.baud_rate || config.baudRate || config.velocidade || '',
      status: device.status || device.state || (device.api_enabled === false ? 'disabled' : 'online'),
      supports_stream: Boolean(device.supports_stream || device.stream || type === 'camera'),
      supports_snapshot: Boolean(device.supports_snapshot || device.snapshot || type === 'camera'),
      snapshot_url: device.snapshot_url || '',
      stream_url: device.stream_url || '',
      metadata: device.metadata || device
    };
  }

  function localListDevices(config) {
    return Promise.all([
      localGet(config, '/api/scales'),
      localGet(config, '/api/cameras')
    ]).then(function (responses) {
      var scalesRes = responses[0];
      var camerasRes = responses[1];
      var scales = (scalesRes.success ? extractList(scalesRes, ['scales', 'balancas', 'devices']) : []).map(function (d) { return normalizeDevice(d, 'scale'); }).filter(function (d) { return d.uuid; });
      var cameras = (camerasRes.success ? extractList(camerasRes, ['cameras', 'devices']) : []).map(function (d) { return normalizeDevice(d, 'camera'); }).filter(function (d) { return d.uuid; });
      var ok = Boolean(scalesRes.success || camerasRes.success);

      return {
        success: ok,
        message: ok ? 'Dispositivos ADP listados com sucesso.' : 'Falha na listagem de dispositivos ADP.',
        devices: scales.concat(cameras),
        scales_count: scales.length,
        cameras_count: cameras.length,
        errors: [
          scalesRes.success ? null : errorText(scalesRes, 'Falha ao listar balanças'),
          camerasRes.success ? null : errorText(camerasRes, 'Falha ao listar câmeras')
        ].filter(Boolean)
      };
    });
  }

  function parseUuidList(value) {
    if (Array.isArray(value)) {
      return value.map(function (v) { return String(v || '').trim(); }).filter(Boolean);
    }

    var raw = String(value || '').trim();
    if (!raw) return [];

    if (raw.charAt(0) === '[') {
      try {
        var parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
          return parsed.map(function (v) { return String(v || '').trim(); }).filter(Boolean);
        }
      } catch (e) {}
    }

    return raw.split(',').map(function (v) { return String(v || '').trim(); }).filter(Boolean);
  }

  function readSelectedValues(select) {
    return Array.from(select && select.selectedOptions ? select.selectedOptions : []).map(function (o) { return o.value; }).filter(Boolean);
  }

  function fillDeviceSelect(select, devices, selectedValues) {
    if (!select) return;
    select.innerHTML = '';
    selectedValues = selectedValues || [];

    devices.forEach(function (device) {
      if (!device.uuid) return;
      var opt = document.createElement('option');
      opt.value = device.uuid;
      opt.textContent = device.name + ' (' + device.status + ')';
      opt.dataset.uuid = device.uuid;
      opt.dataset.port = device.port || '';
      opt.dataset.baudRate = device.baud_rate || '';
      opt.dataset.driver = device.driver || '';
      opt.dataset.model = device.model || '';
      opt.dataset.streamUrl = device.stream_url || '';
      opt.dataset.snapshotUrl = device.snapshot_url || '';
      if (selectedValues.indexOf(device.uuid) >= 0) opt.selected = true;
      select.appendChild(opt);
    });
  }

  function syncDerivedFields(scope) {
    var camSelect = q(scope, 'camera-select');
    var selected = camSelect ? readSelectedValues(camSelect) : parseUuidList((q(scope, 'camera-uuids') || {}).value || '');
    var camerasInput = q(scope, 'camera-uuids');
    var qtdInput = q(scope, 'quantidade-cameras');
    var usaCheck = q(scope, 'usa-cameras');

    if (camerasInput) camerasInput.value = JSON.stringify(selected);
    if (qtdInput) qtdInput.value = String(selected.length);
    if (usaCheck) usaCheck.checked = selected.length > 0;
  }

  function autoFillScaleFields(scope, option) {
    if (!option || !option.value) return;
    setValue(q(scope, 'scale-uuid'), option.dataset.uuid || option.value);
    setValue(q(scope, 'porta-serial'), option.dataset.port || '');
    setValue(q(scope, 'baud-rate'), option.dataset.baudRate || '');
    setValue(q(scope, 'integrador'), 'adp');
  }

  function renderDevices(scope, devices) {
    var scales = (devices || []).filter(function (d) { return d.type === 'scale'; });
    var cameras = (devices || []).filter(function (d) { return d.type === 'camera'; });
    var scaleSelect = q(scope, 'scale-select');
    var cameraSelect = q(scope, 'camera-select');
    var currentScale = String((q(scope, 'scale-uuid') || {}).value || '').trim();
    var currentCameras = parseUuidList((q(scope, 'camera-uuids') || {}).value || '');

    fillDeviceSelect(scaleSelect, scales, currentScale ? [currentScale] : []);
    fillDeviceSelect(cameraSelect, cameras, currentCameras);

    if (scaleSelect && scaleSelect.value) {
      autoFillScaleFields(scope, scaleSelect.options[scaleSelect.selectedIndex]);
    }

    syncDerivedFields(scope);
    updateLog(scope, 'Discovery concluído. Balanças: ' + scales.length + ' | Câmeras: ' + cameras.length);
  }

  function upsertConfigOption(scope, cfg, selectIt) {
    if (!cfg || !cfg.id) return;
    var select = configSelect(scope);
    if (!select) return;

    var value = String(cfg.id);
    var option = optionByValue(select, value);
    if (!option) {
      option = document.createElement('option');
      option.value = value;
      select.appendChild(option);
    }

    option.textContent = '#' + value + ' - ' + (cfg.descricao || cfg.base_url || 'Configuração ADP');
    option.dataset.tokenMasked = cfg.global_token_masked || cfg.masked_global_token || '';
    option.dataset.baseUrl = (cfg.base_url || cfg.baseUrl || '').replace(/\/+$/, '');
    option.dataset.descricao = cfg.descricao || '';
    option.dataset.tokenType = cfg.global_token_type || cfg.token_type || 'x_adp_api_token';
    option.dataset.tokenHeader = cfg.global_token_header || cfg.token_header || 'X-ADP-API-TOKEN';
    option.dataset.timeoutMs = String(cfg.timeout_ms || 5000);

    if (selectIt) {
      select.value = value;
      select.dataset.currentValue = value;
      scope.dataset.serverIntegradorConfigId = value;
    }

    applySelectedConfig(scope, { clear: false });
  }

  function loadConfigsIfNeeded(scope) {
    var select = configSelect(scope);
    if (!select || (select.options && select.options.length > 1)) {
      ensureSelectedFromServer(scope);
      return Promise.resolve();
    }

    return getJson('/adp/discovery/configs').then(function (res) {
      if (!res.success) throw new Error(errorText(res, 'Falha ao carregar configurações ADP'));
      var current = String(select.dataset.currentValue || scope.dataset.serverIntegradorConfigId || select.value || '').trim();
      (res.configs || []).forEach(function (cfg) { upsertConfigOption(scope, cfg, false); });
      if (current && optionByValue(select, current)) {
        select.value = current;
      }
      applySelectedConfig(scope, { clear: false });
    }).catch(function (err) {
      updateLog(scope, 'Erro ao carregar configs ADP: ' + err.message);
    });
  }

  function salvarConfigAdp(scope) {
    var selected = selectedConfigId(scope);
    var descricao = String((q(scope, 'cfg-descricao') || {}).value || '').trim();
    var baseUrl = String((q(scope, 'cfg-base-url') || {}).value || '').trim().replace(/\/+$/, '');
    var tokenType = String((q(scope, 'cfg-token-type') || {}).value || 'x_adp_api_token').trim();
    var token = String((q(scope, 'cfg-global-token') || {}).value || '').trim();

    if (!descricao || !baseUrl) {
      updateLog(scope, 'Informe descrição e Base URL para salvar a configuração ADP.');
      return Promise.resolve();
    }

    updateLog(scope, 'Salvando configuração ADP...');

    return getJson('/adp/discovery/configs/save', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken()
      },
      body: JSON.stringify({
        id: selected || null,
        descricao: descricao,
        base_url: baseUrl,
        global_token_type: tokenType,
        global_token: token,
        global_token_enabled: true,
        global_token_header: tokenType === 'bearer' ? 'Authorization' : 'X-ADP-API-TOKEN',
        timeout_ms: 5000,
        ativo: true
      })
    }).then(function (res) {
      if (!res.success) throw new Error(errorText(res, 'Falha ao salvar configuração ADP'));
      updateLog(scope, 'Configuração ADP salva com sucesso.');
      if (q(scope, 'cfg-global-token')) q(scope, 'cfg-global-token').value = '';
      upsertConfigOption(scope, res.config, !selected || String(selected) === String(res.config && res.config.id));
    }).catch(function (err) {
      updateLog(scope, 'Erro ao salvar configuração ADP: ' + err.message);
    });
  }

  function novaConfigAdp(scope) {
    var select = configSelect(scope);
    if (select) select.value = '';
    setValue(q(scope, 'integrador-config-id-hidden'), '');
    setValue(q(scope, 'backend-server-address'), '');
    setValue(q(scope, 'cfg-descricao'), '');
    setValue(q(scope, 'cfg-base-url'), '');
    setValue(q(scope, 'cfg-global-token'), '');
    setValue(q(scope, 'cfg-token-type'), 'x_adp_api_token');
    updateTokenHint(scope, null);
    updateLog(scope, 'Nova configuração ADP. Informe os dados e salve.');
  }

  function excluirConfigAdp(scope) {
    var cfg = currentConfig(scope);
    if (!cfg || !cfg.id) {
      updateLog(scope, 'Selecione uma configuração ADP para excluir.');
      return Promise.resolve();
    }

    if (!confirm('Deseja excluir/inativar esta configuração ADP?')) {
      return Promise.resolve();
    }

    updateLog(scope, 'Removendo configuração ADP...');
    return getJson('/adp/discovery/configs/delete/' + encodeURIComponent(cfg.id), {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken()
      }
    }).then(function (res) {
      if (!res.success) throw new Error(errorText(res, 'Falha ao remover configuração ADP'));
      updateLog(scope, res.message || 'Configuração ADP removida.');
      var select = configSelect(scope);
      var option = optionByValue(select, cfg.id);
      if (option) option.remove();
      novaConfigAdp(scope);
    }).catch(function (err) {
      updateLog(scope, 'Erro ao remover configuração ADP: ' + err.message);
    });
  }

  function testAdp(scope) {
    updateLog(scope, 'Testando conexão ADP...');
    return runtimeConfig(scope).then(function (runtime) {
      if (!runtime.success) throw new Error(errorText(runtime, 'Falha ao obter configuração ADP'));
      return localGet(runtime.config, '/api/health');
    }).then(function (res) {
      updateLog(scope, (res.success ? 'Conexão ADP OK.' : 'Falha na conexão ADP.') + ' ' + (res.success ? (res.message || '') : errorText(res, '')));
    }).catch(function (err) {
      updateLog(scope, 'Erro ao testar conexão: ' + err.message);
    });
  }

  function loadDevices(scope) {
    updateLog(scope, 'Consultando dispositivos ADP...');
    return runtimeConfig(scope).then(function (runtime) {
      if (!runtime.success) throw new Error(errorText(runtime, 'Falha ao obter configuração ADP'));
      return localListDevices(runtime.config);
    }).then(function (res) {
      if (!res.success) throw new Error(errorText(res, 'Falha na listagem de dispositivos'));
      renderDevices(scope, res.devices || []);
    }).catch(function (err) {
      updateLog(scope, 'Erro no discovery: ' + err.message);
    });
  }

  function syncDevices(scope) {
    var cfg = currentConfig(scope);
    if (!cfg || !cfg.id) {
      updateLog(scope, 'Salve ou selecione uma configuração ADP antes da sincronização.');
      return Promise.resolve();
    }

    updateLog(scope, 'Sincronizando dispositivos ADP...');
    return runtimeConfig(scope).then(function (runtime) {
      if (!runtime.success) throw new Error(errorText(runtime, 'Falha ao obter configuração ADP'));
      return localListDevices(runtime.config);
    }).then(function (res) {
      if (!res.success) throw new Error(errorText(res, 'Falha na listagem de dispositivos'));
      renderDevices(scope, res.devices || []);
      return getJson('/adp/discovery/import-devices?integrador_config_id=' + encodeURIComponent(cfg.id), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken()
        },
        body: JSON.stringify({ devices: res.devices || [], source: 'browser_adp' })
      });
    }).then(function (res) {
      if (!res.success) {
        updateLog(scope, 'Sync com falha. ' + (res.devices_synced || 0) + ' dispositivos sincronizados. ' + errorText(res, ''));
        return;
      }
      updateLog(scope, 'Sync concluído. ' + (res.devices_synced || 0) + ' dispositivos sincronizados.');
    }).catch(function (err) {
      updateLog(scope, 'Erro ao sincronizar dispositivos: ' + err.message);
    });
  }

  function disableLegacySections(form) {
    if (!form) return;
    form.querySelectorAll('.js-legacy-section input, .js-legacy-section select, .js-legacy-section textarea').forEach(function (el) {
      el.disabled = true;
      el.removeAttribute('required');
    });
  }

  function prepareFormSubmit(form) {
    if (!form) return;
    disableLegacySections(form);
    form.querySelectorAll('.adp-cadastro-guided').forEach(function (scope) {
      applySelectedConfig(scope, { clear: false });
      syncDerivedFields(scope);
    });
  }

  function rootFromEventTarget(target) {
    return target ? target.closest('.adp-cadastro-guided') : null;
  }

  function handleAction(action, scope) {
    if (!scope) return;

    if (action === 'testar-conexao' || action === 'btn-testar') return testAdp(scope);
    if (action === 'buscar-dispositivos' || action === 'btn-buscar') return loadDevices(scope);
    if (action === 'sincronizar-dispositivos' || action === 'btn-sync') return syncDevices(scope);
    if (action === 'salvar-config' || action === 'btn-salvar-config') return salvarConfigAdp(scope);
    if (action === 'nova-config' || action === 'btn-nova-config') return novaConfigAdp(scope);
    if (action === 'excluir-config' || action === 'btn-excluir-config') return excluirConfigAdp(scope);
  }

  function bindGlobalEvents() {
    if (initializedGlobalEvents) return;
    initializedGlobalEvents = true;

    document.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-adp-action], [data-adp="btn-testar"], [data-adp="btn-buscar"], [data-adp="btn-sync"], [data-adp="btn-salvar-config"], [data-adp="btn-nova-config"], [data-adp="btn-excluir-config"]');
      if (!btn) return;

      var scope = rootFromEventTarget(btn);
      if (!scope) return;

      event.preventDefault();
      var action = btn.dataset.adpAction || btn.dataset.adp;
      handleAction(action, scope);
    });

    document.addEventListener('change', function (event) {
      var target = event.target;
      var scope = rootFromEventTarget(target);
      if (!scope) return;

      if (target.matches('[data-adp="integrador-config-id"]')) {
        target.dataset.currentValue = target.value || '';
        scope.dataset.serverIntegradorConfigId = target.value || '';
        applySelectedConfig(scope, { clear: false });
        updateLog(scope, 'Configuração ADP selecionada: #' + (target.value || ''));
        return;
      }

      if (target.matches('[data-adp="scale-select"]')) {
        autoFillScaleFields(scope, target.options[target.selectedIndex]);
        return;
      }

      if (target.matches('[data-adp="camera-select"]')) {
        syncDerivedFields(scope);
      }
    });

    document.addEventListener('submit', function (event) {
      prepareFormSubmit(event.target);
    }, true);
  }

  function initScope(scope) {
    if (!scope || scope.dataset.adpDiscoveryInitialized === '1') return;
    scope.dataset.adpDiscoveryInitialized = '1';
    ensureSelectedFromServer(scope);
    syncDerivedFields(scope);
    loadConfigsIfNeeded(scope);
  }

  function scan(root) {
    root = root || document;

    if (root.matches && root.matches('.adp-cadastro-guided')) {
      initScope(root);
    }

    if (root.querySelectorAll) {
      root.querySelectorAll('.adp-cadastro-guided').forEach(function (scope) {
        initScope(scope);
      });
    }
  }

  window.initAdpDeviceDiscovery = scan;
  window.AdpDeviceDiscovery = {
    scan: scan,
    applySelectedConfig: applySelectedConfig,
    testAdp: testAdp,
    loadDevices: loadDevices,
    syncDevices: syncDevices
  };

  bindGlobalEvents();

  document.addEventListener('DOMContentLoaded', function () {
    scan(document);
  });

  document.addEventListener('shown.bs.modal', function (event) {
    scan(event.target || document);
  });

  if (window.jQuery) {
    window.jQuery(document).on('shown.bs.modal', function (event) {
      scan(event.target || document);
    });

    window.jQuery(document).ajaxComplete(function () {
      scan(document);
    });
  }

  if (window.MutationObserver) {
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        Array.from(mutation.addedNodes || []).forEach(function (node) {
          if (node && node.nodeType === 1) scan(node);
        });
      });
    });

    document.addEventListener('DOMContentLoaded', function () {
      if (document.body) observer.observe(document.body, { childList: true, subtree: true });
    });
  }
})();
