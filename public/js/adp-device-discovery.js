(function () {
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

  function q(scope, key) { return scope.querySelector('[data-adp="' + key + '"]'); }

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
    log.textContent = '[' + new Date().toISOString() + '] ' + text + "\n" + log.textContent;
  }

  function isLoopbackUrl(url) {
    try {
      var parsed = new URL(url, window.location.href);
      var host = String(parsed.hostname || '').toLowerCase();
      return ['127.0.0.1', 'localhost', '::1', '0.0.0.0'].indexOf(host) >= 0;
    } catch (e) {
      return false;
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

  function mapOption(device) {
    var name = device.name || device.uuid || 'sem nome';
    var status = device.status || 'unknown';
    return { value: device.uuid || '', label: name + ' (' + status + ')' };
  }

  function fillSelect(select, items, selectedValues) {
    if (!select) return;
    select.innerHTML = '';
    (items || []).forEach(function (item) {
      if (!item.value) return;
      var opt = document.createElement('option');
      opt.value = item.value;
      opt.textContent = item.label;
      if ((selectedValues || []).indexOf(item.value) >= 0) opt.selected = true;
      select.appendChild(opt);
    });
  }

  function readSelectedValues(select) {
    return Array.from(select && select.selectedOptions ? select.selectedOptions : []).map(function (o) { return o.value; }).filter(Boolean);
  }

  function getVisibleConfigId(scope) {
    var i = q(scope, 'integrador-config-id');
    return i ? String(i.value || '').trim() : '';
  }

  function getHiddenConfigId(scope) {
    var i = q(scope, 'integrador-config-id-hidden');
    return i ? String(i.value || '').trim() : '';
  }

  function getConfigId(scope) {
    // O select é apenas a interface visual. O hidden é o campo submetido ao Laravel.
    // Durante edição, se o select ainda não foi carregado via AJAX, o hidden mantém
    // o ADP já vinculado à balança e impede voltar para o primeiro cadastro.
    return getVisibleConfigId(scope) || getHiddenConfigId(scope);
  }

  function selectedConfigDataset(scope) {
    var select = q(scope, 'integrador-config-id');
    if (!select) return null;
    var selected = select.options[select.selectedIndex];
    return selected && selected.dataset ? selected.dataset : null;
  }

  function syncSelectedConfigBindings(scope) {
    var select = q(scope, 'integrador-config-id');
    var hidden = q(scope, 'integrador-config-id-hidden');
    var hiddenBase = q(scope, 'backend-server-address');
    var selected = select ? select.options[select.selectedIndex] : null;
    var selectedValue = select ? String(select.value || '').trim() : '';

    if (hidden) {
      hidden.value = selectedValue || getHiddenConfigId(scope);
    }

    if (hiddenBase) {
      hiddenBase.value = selected && selected.dataset ? (selected.dataset.baseUrl || '') : '';
    }
  }

  function currentBaseUrl(scope) {
    var editorValue = (q(scope, 'cfg-base-url') || {}).value || '';
    var dataset = selectedConfigDataset(scope);
    return String(editorValue || (dataset ? dataset.baseUrl : '') || '').trim();
  }

  function currentTokenType(scope) {
    var editorValue = (q(scope, 'cfg-token-type') || {}).value || '';
    var dataset = selectedConfigDataset(scope);
    return String(editorValue || (dataset ? dataset.tokenType : '') || 'x_adp_api_token').trim();
  }

  function updateConfigHint(scope) {
    var select = q(scope, 'integrador-config-id');
    var hint = q(scope, 'config-token-mask');
    if (!select || !hint) return;
    var selected = select.options[select.selectedIndex];
    var mask = selected && selected.dataset ? selected.dataset.tokenMasked : '';
    var baseUrl = selected && selected.dataset ? selected.dataset.baseUrl : '';
    var text = [];
    if (mask) text.push('Token global mascarado: ' + mask);
    hint.textContent = text.join(' | ');
  }

  function fillConfigEditorFromSelect(scope) {
    var select = q(scope, 'integrador-config-id');
    if (!select) return;

    var selected = select.options[select.selectedIndex];
    if (!selected || !selected.value || !selected.dataset) return;

    var descricao = q(scope, 'cfg-descricao');
    var baseUrl = q(scope, 'cfg-base-url');
    var tokenType = q(scope, 'cfg-token-type');

    if (descricao) descricao.value = selected.dataset.descricao || '';
    if (baseUrl) baseUrl.value = selected.dataset.baseUrl || '';
    if (tokenType) tokenType.value = selected.dataset.tokenType || 'x_adp_api_token';

    syncSelectedConfigBindings(scope);
    updateConfigHint(scope);
  }

  function upsertConfigOption(scope, cfg, selectIt) {
    if (!cfg || !cfg.id) return;

    var select = q(scope, 'integrador-config-id');
    if (!select) return;

    var value = String(cfg.id);
    var option = Array.from(select.options).find(function (opt) { return String(opt.value) === value; });

    if (!option) {
      option = document.createElement('option');
      option.value = value;
      select.appendChild(option);
    }

    option.textContent = '#' + value + ' - ' + (cfg.descricao || cfg.base_url || 'Configuração ADP');
    option.dataset.tokenMasked = cfg.global_token_masked || '';
    option.dataset.baseUrl = cfg.base_url || '';
    option.dataset.descricao = cfg.descricao || '';
    option.dataset.tokenType = cfg.global_token_type || 'x_adp_api_token';
    option.dataset.tokenHeader = cfg.global_token_header || 'X-ADP-API-TOKEN';
    option.dataset.timeoutMs = String(cfg.timeout_ms || 5000);

    if (selectIt) {
      select.value = value;
      syncSelectedConfigBindings(scope);
    }

    updateConfigHint(scope);
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

  function encodeUuidList(values) {
    return JSON.stringify(parseUuidList(values));
  }

  function syncDerivedFields(scope) {
    var camSelect = q(scope, 'camera-select');
    var selected = camSelect ? readSelectedValues(camSelect) : [];
    var json = JSON.stringify(selected);

    var camerasInput = q(scope, 'camera-uuids');
    var qtdInput = q(scope, 'quantidade-cameras');
    var usaCheck = q(scope, 'usa-cameras');

    if (camerasInput) camerasInput.value = json;
    if (qtdInput) qtdInput.value = String(selected.length);
    if (usaCheck) usaCheck.checked = selected.length > 0;
  }

  function autoFillScaleFields(scope, scale) {
    if (!scale) return;
    var mappings = {
      'scale-uuid': scale.uuid || '',
      'porta-serial': scale.port || '',
      'baud-rate': (scale.baud_rate || ''),
      'integrador': 'adp'
    };

    Object.keys(mappings).forEach(function (key) {
      var input = q(scope, key);
      if (input) input.value = mappings[key];
    });
  }

  function runtimeConfig(scope) {
    var configId = getConfigId(scope);
    var tokenTyped = (q(scope, 'cfg-global-token') || {}).value || '';
    var baseUrlTyped = currentBaseUrl(scope);
    var tokenTypeTyped = currentTokenType(scope);

    if (!configId) {
      return Promise.resolve({
        success: true,
        config: {
          id: null,
          base_url: baseUrlTyped,
          global_token: tokenTyped,
          global_token_type: tokenTypeTyped,
          global_token_header: tokenTypeTyped === 'bearer' ? 'Authorization' : 'X-ADP-API-TOKEN',
          timeout_ms: 5000
        }
      });
    }

    return getJson('/adp/discovery/configs/runtime?integrador_config_id=' + encodeURIComponent(configId))
      .then(function (res) {
        if (!res.success) return res;
        res.config = res.config || {};

        // Quando há configuração selecionada, o ID é a fonte da verdade.
        // Não misturar token de uma configuração com Base URL digitada de outra.
        if (tokenTyped) {
          res.config.global_token = tokenTyped;
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
      headers: localHeaders(config)
    }).then(function (r) {
      return r.json().catch(function () {
        return { success: false, message: 'Resposta inválida do ADP local.' };
      }).then(function (json) {
        if (json && typeof json === 'object' && !Array.isArray(json)) {
          json.http_status = r.status;
          if (!r.ok && !json.message) json.message = 'Falha HTTP ' + r.status + ' no ADP local.';
        }
        if (Array.isArray(json)) {
          json = { success: r.ok, data: json, http_status: r.status };
        }
        if (r.ok && json && typeof json === 'object' && !Object.prototype.hasOwnProperty.call(json, 'success')) {
          json.success = true;
        }
        return json;
      });
    }).catch(function (err) {
      return {
        success: false,
        message: 'Não foi possível acessar a API ADP. Verifique se o serviço está ativo, se a URL/porta estão corretas e se o token foi informado corretamente.',
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
      model: device.model || device.modelo || config.model || config.modelo || null,
      driver: device.driver || device.driver_name || config.driver || null,
      protocol: device.protocol || device.protocolo || config.protocol || null,
      host: device.host || device.ip || config.host || config.ip || null,
      port: device.port || device.porta || device.porta_serial || config.port || config.porta || null,
      baud_rate: device.baud_rate || device.baudRate || device.velocidade || config.baud_rate || config.baudRate || config.velocidade || null,
      status: device.status || device.state || (device.api_enabled === false ? 'disabled' : 'online'),
      stable: Boolean(device.stable || device.estavel),
      last_weight: Number(device.last_weight || device.weight || device.peso || 0),
      unit: device.unit || device.unidade || 'kg',
      clients: Number(device.clients || device.clientes || 0),
      supports_stream: Boolean(device.supports_stream || device.stream || type === 'camera'),
      supports_snapshot: Boolean(device.supports_snapshot || device.snapshot || type === 'camera'),
      snapshot_url: device.snapshot_url || null,
      stream_url: device.stream_url || null,
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
        errors: [scalesRes.success ? null : errorText(scalesRes, 'Falha ao listar balanças'), camerasRes.success ? null : errorText(camerasRes, 'Falha ao listar câmeras')].filter(Boolean)
      };
    });
  }

  function renderDevices(scope, devices) {
    var scales = (devices || []).filter(function (d) { return d.type === 'scale'; });
    var cameras = (devices || []).filter(function (d) { return d.type === 'camera'; });

    var scaleSelect = q(scope, 'scale-select');
    var cameraSelect = q(scope, 'camera-select');
    var currentScale = (q(scope, 'scale-uuid') || {}).value || '';
    var currentCameras = parseUuidList((q(scope, 'camera-uuids') || {}).value || '');

    fillSelect(scaleSelect, scales.map(mapOption), [currentScale]);
    fillSelect(cameraSelect, cameras.map(mapOption), currentCameras);

    if (scaleSelect && scaleSelect.value) {
      var selected = scales.find(function (s) { return s.uuid === scaleSelect.value; });
      autoFillScaleFields(scope, selected);
    }
    syncDerivedFields(scope);

    updateLog(scope, 'Discovery concluído. Balanças: ' + scales.length + ' | Câmeras: ' + cameras.length);
  }

  function loadConfigs(scope) {
    return getJson('/adp/discovery/configs')
      .then(function (res) {
        if (!res.success) throw new Error('Falha ao carregar configurações ADP');
        var select = q(scope, 'integrador-config-id');
        if (!select) return;
        var defaultId = String(scope.dataset.defaultIntegradorConfigId || getHiddenConfigId(scope) || '').trim();
        select.innerHTML = '<option value="">Selecione...</option>';
        (res.configs || []).forEach(function (cfg) {
          upsertConfigOption(scope, cfg, String(cfg.id) === String(defaultId));
        });
        if (defaultId && !select.value) {
          select.value = defaultId;
        }
        syncSelectedConfigBindings(scope);
        updateConfigHint(scope);
        fillConfigEditorFromSelect(scope);
      })
      .catch(function (err) {
        updateLog(scope, 'Erro ao carregar configs ADP: ' + err.message);
      });
  }

  function salvarConfigAdp(scope) {
    var descricao = (q(scope, 'cfg-descricao') || {}).value || '';
    var baseUrl = (q(scope, 'cfg-base-url') || {}).value || '';
    var tokenType = (q(scope, 'cfg-token-type') || {}).value || 'x_adp_api_token';
    var token = (q(scope, 'cfg-global-token') || {}).value || '';
    var selectedId = getConfigId(scope);

    if (!descricao || !baseUrl) {
      return updateLog(scope, 'Informe descrição e base URL para salvar configuração ADP.');
    }

    var payload = {
      id: selectedId || null,
      descricao: descricao,
      base_url: baseUrl,
      global_token_type: tokenType,
      global_token: token,
      global_token_enabled: true,
      global_token_header: tokenType === 'bearer' ? 'Authorization' : 'X-ADP-API-TOKEN',
      timeout_ms: 5000,
      ativo: true
    };

    updateLog(scope, 'Salvando configuração ADP...');
    return getJson('/adp/discovery/configs/save', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken()
      },
      body: JSON.stringify(payload)
    }).then(function (res) {
      if (!res.success) {
        throw new Error(res.message || 'Falha ao salvar configuração ADP');
      }
      updateLog(scope, 'Configuração ADP salva com sucesso.');
      if (q(scope, 'cfg-global-token')) q(scope, 'cfg-global-token').value = '';

      // Seleciona imediatamente a configuração retornada pelo backend.
      // Não depende do reload do combo, evitando perder o vínculo quando a listagem falhar momentaneamente.
      upsertConfigOption(scope, res.config, true);
      fillConfigEditorFromSelect(scope);
      updateConfigHint(scope);
      loadConfigs(scope);
    }).catch(function (err) {
      updateLog(scope, 'Erro ao salvar configuração ADP: ' + err.message);
    });
  }


  function novaConfigAdp(scope) {
    var select = q(scope, 'integrador-config-id');
    if (select) select.value = '';
    var hidden = q(scope, 'integrador-config-id-hidden');
    if (hidden) hidden.value = '';
    var hiddenBase = q(scope, 'backend-server-address');
    if (hiddenBase) hiddenBase.value = '';
    ['cfg-descricao', 'cfg-base-url', 'cfg-global-token'].forEach(function (key) {
      var el = q(scope, key);
      if (el) el.value = '';
    });
    var tokenType = q(scope, 'cfg-token-type');
    if (tokenType) tokenType.value = 'x_adp_api_token';
    updateConfigHint(scope);
    updateLog(scope, 'Nova configuração ADP. Informe os dados e salve.');
  }

  function excluirConfigAdp(scope) {
    var configId = getConfigId(scope);
    if (!configId) {
      updateLog(scope, 'Selecione uma configuração ADP para excluir.');
      return;
    }

    if (!confirm('Deseja excluir/inativar esta configuração ADP?')) {
      return;
    }

    updateLog(scope, 'Removendo configuração ADP...');
    return getJson('/adp/discovery/configs/delete/' + encodeURIComponent(configId), {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken()
      }
    }).then(function (res) {
      if (!res.success) throw new Error(res.message || 'Falha ao remover configuração ADP.');
      updateLog(scope, res.message || 'Configuração ADP removida com sucesso.');
      novaConfigAdp(scope);
      return loadConfigs(scope);
    }).catch(function (err) {
      updateLog(scope, 'Erro ao remover configuração ADP: ' + err.message);
    });
  }

  function loadDevices(scope) {
    if (!getConfigId(scope) && !currentBaseUrl(scope)) {
      updateLog(scope, 'Informe a Base URL ADP ou selecione uma configuração antes de buscar dispositivos.');
      return;
    }

    updateLog(scope, 'Consultando dispositivos ADP...');

    return runtimeConfig(scope).then(function (runtime) {
      if (!runtime.success) throw new Error(errorText(runtime, 'Falha ao obter configuração ADP.'));
      return localListDevices(runtime.config);
    }).then(function (res) {
      if (!res.success) throw new Error(errorText(res, 'Falha na listagem de dispositivos'));
      renderDevices(scope, res.devices || []);
    }).catch(function (err) {
      updateLog(scope, 'Erro no discovery: ' + err.message);
    });
  }

  function testAdp(scope) {
    if (!getConfigId(scope) && !currentBaseUrl(scope)) {
      return updateLog(scope, 'Informe a Base URL ADP ou selecione uma configuração antes do teste.');
    }

    updateLog(scope, 'Testando conexão ADP...');

    return runtimeConfig(scope).then(function (runtime) {
      if (!runtime.success) throw new Error(errorText(runtime, 'Falha ao obter configuração ADP.'));
      return localGet(runtime.config, '/api/health');
    }).then(function (res) {
      updateLog(scope, (res.success ? 'Conexão ADP OK.' : 'Falha na conexão ADP.') + ' ' + (res.success ? (res.message || '') : errorText(res, '')));
    }).catch(function (err) {
      updateLog(scope, 'Erro ao testar conexão: ' + err.message);
    });
  }

  function syncDevices(scope) {
    var configId = getConfigId(scope);
    if (!configId) return updateLog(scope, 'Salve ou selecione uma configuração ADP antes da sincronização.');

    updateLog(scope, 'Sincronizando dispositivos ADP...');

    return runtimeConfig(scope).then(function (runtime) {
      if (!runtime.success) throw new Error(errorText(runtime, 'Falha ao obter configuração ADP.'));
      return localListDevices(runtime.config);
    }).then(function (res) {
      if (!res.success) throw new Error(errorText(res, 'Falha na listagem de dispositivos'));
      renderDevices(scope, res.devices || []);
      return getJson('/adp/discovery/import-devices?integrador_config_id=' + encodeURIComponent(configId), {
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

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.adp-cadastro-guided').forEach(function (scope) {
      if (scope.dataset.adpDiscoveryInitialized === '1') return;
      scope.dataset.adpDiscoveryInitialized = '1';
      var btnTestar = q(scope, 'btn-testar');
      var btnBuscar = q(scope, 'btn-buscar');
      var btnSync = q(scope, 'btn-sync');
      var scaleSelect = q(scope, 'scale-select');
      var cameraSelect = q(scope, 'camera-select');
      var configSelect = q(scope, 'integrador-config-id');
      var btnSalvarCfg = q(scope, 'btn-salvar-config');
      var btnNovaCfg = q(scope, 'btn-nova-config');
      var btnExcluirCfg = q(scope, 'btn-excluir-config');
      var baseUrlInput = q(scope, 'cfg-base-url');

      btnTestar && btnTestar.addEventListener('click', function () { testAdp(scope); });
      btnBuscar && btnBuscar.addEventListener('click', function(){ loadDevices(scope); });
      btnSync && btnSync.addEventListener('click', function () { syncDevices(scope); });

      scaleSelect && scaleSelect.addEventListener('change', function () {
        var scaleUuidInput = q(scope, 'scale-uuid');
        if (scaleUuidInput) scaleUuidInput.value = scaleSelect.value;
      });

      cameraSelect && cameraSelect.addEventListener('change', function(){ syncDerivedFields(scope); });
      configSelect && configSelect.addEventListener('change', function(){
        syncSelectedConfigBindings(scope);
        fillConfigEditorFromSelect(scope);
      });
      baseUrlInput && baseUrlInput.addEventListener('input', function(){ updateConfigHint(scope); });
      btnSalvarCfg && btnSalvarCfg.addEventListener('click', function(){ salvarConfigAdp(scope); });
      btnNovaCfg && btnNovaCfg.addEventListener('click', function(){ novaConfigAdp(scope); });
      btnExcluirCfg && btnExcluirCfg.addEventListener('click', function(){ excluirConfigAdp(scope); });

      var ownerForm = scope.closest('form');
      if (ownerForm) {
        ownerForm.addEventListener('submit', function () {
          syncSelectedConfigBindings(scope);
        });
      }

      loadConfigs(scope);
    });
  });
})();
