(function () {
  function getJson(url, opts) {
    return fetch(url, Object.assign({ headers: { 'Accept': 'application/json' } }, opts || {})).then(function (r) {
      return r.json().catch(function () {
        return { success: false, message: 'Resposta inválida do servidor.' };
      }).then(function (json) {
        if (!r.ok && json && !json.message) {
          json.message = 'Falha HTTP ' + r.status;
        }
        if (json && typeof json === 'object') {
          json.http_status = r.status;
        }
        return json;
      });
    });
  }

  function q(scope, key) { return scope.querySelector('[data-adp="' + key + '"]'); }

  function updateLog(scope, text) {
    var log = q(scope, 'log');
    if (!log) return;
    log.textContent = '[' + new Date().toISOString() + '] ' + text + "\n" + log.textContent;
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

  function getConfigId(scope) {
    var i = q(scope, 'integrador-config-id');
    return i ? String(i.value || '').trim() : '';
  }

  function updateConfigHint(scope) {
    var select = q(scope, 'integrador-config-id');
    var hint = q(scope, 'config-token-mask');
    if (!select || !hint) return;
    var selected = select.options[select.selectedIndex];
    var mask = selected && selected.dataset ? selected.dataset.tokenMasked : '';
    hint.textContent = mask ? ('Token global mascarado: ' + mask) : '';
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

    updateConfigHint(scope);
  }

  function syncDerivedFields(scope) {
    var camSelect = q(scope, 'camera-select');
    var selected = camSelect ? readSelectedValues(camSelect) : [];
    var csv = selected.join(',');

    var camerasInput = q(scope, 'camera-uuids');
    var qtdInput = q(scope, 'quantidade-cameras');
    var usaCheck = q(scope, 'usa-cameras');

    if (camerasInput) camerasInput.value = csv;
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

  function loadConfigs(scope) {
    return getJson('/adp/discovery/configs')
      .then(function (res) {
        if (!res.success) throw new Error('Falha ao carregar configurações ADP');
        var select = q(scope, 'integrador-config-id');
        if (!select) return;
        var defaultId = scope.dataset.defaultIntegradorConfigId || '';
        select.innerHTML = '<option value="">Selecione...</option>';
        (res.configs || []).forEach(function (cfg) {
          var option = document.createElement('option');
          option.value = String(cfg.id);
          option.textContent = '#' + cfg.id + ' - ' + (cfg.descricao || cfg.base_url || 'Configuração ADP');
          if (String(cfg.id) === String(defaultId)) option.selected = true;
          option.dataset.tokenMasked = cfg.global_token_masked || '';
          option.dataset.baseUrl = cfg.base_url || '';
          option.dataset.descricao = cfg.descricao || '';
          option.dataset.tokenType = cfg.global_token_type || 'x_adp_api_token';
          option.dataset.tokenHeader = cfg.global_token_header || 'X-ADP-API-TOKEN';
          option.dataset.timeoutMs = String(cfg.timeout_ms || 5000);
          select.appendChild(option);
        });
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
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
      },
      body: JSON.stringify(payload)
    }).then(function (res) {
      if (!res.success) {
        throw new Error(res.message || 'Falha ao salvar configuração ADP');
      }
      updateLog(scope, 'Configuração ADP salva com sucesso.');
      if (q(scope, 'cfg-global-token')) q(scope, 'cfg-global-token').value = '';
      loadConfigs(scope).then(function(){
        if (q(scope, 'integrador-config-id')) q(scope, 'integrador-config-id').value = String(res.config.id);
        updateConfigHint(scope);
      });
    }).catch(function (err) {
      updateLog(scope, 'Erro ao salvar configuração ADP: ' + err.message);
    });
  }

  function loadDevices(scope) {
    var configId = getConfigId(scope);
    if (!configId) {
      updateLog(scope, 'Salve ou selecione uma configuração ADP antes de buscar dispositivos.');
      return;
    }

    updateLog(scope, 'Consultando dispositivos ADP...');
    return getJson('/adp/discovery/devices?integrador_config_id=' + encodeURIComponent(configId))
      .then(function (res) {
        if (!res.success) throw new Error(res.message || (res.errors || []).join(' | ') || 'Falha na listagem de dispositivos');

        var devices = res.devices || [];
        var scales = devices.filter(function (d) { return d.type === 'scale'; });
        var cameras = devices.filter(function (d) { return d.type === 'camera'; });

        var scaleSelect = q(scope, 'scale-select');
        var cameraSelect = q(scope, 'camera-select');
        var currentScale = (q(scope, 'scale-uuid') || {}).value || '';
        var currentCameras = ((q(scope, 'camera-uuids') || {}).value || '').split(',').map(function(v){ return v.trim(); }).filter(Boolean);

        fillSelect(scaleSelect, scales.map(mapOption), [currentScale]);
        fillSelect(cameraSelect, cameras.map(mapOption), currentCameras);

        if (scaleSelect && scaleSelect.value) {
          var selected = scales.find(function (s) { return s.uuid === scaleSelect.value; });
          autoFillScaleFields(scope, selected);
        }
        syncDerivedFields(scope);

        updateLog(scope, 'Discovery concluído. Balanças: ' + scales.length + ' | Câmeras: ' + cameras.length);
      })
      .catch(function (err) {
        updateLog(scope, 'Erro no discovery: ' + err.message);
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.adp-cadastro-guided').forEach(function (scope) {
      var btnTestar = q(scope, 'btn-testar');
      var btnBuscar = q(scope, 'btn-buscar');
      var btnSync = q(scope, 'btn-sync');
      var scaleSelect = q(scope, 'scale-select');
      var cameraSelect = q(scope, 'camera-select');
      var configSelect = q(scope, 'integrador-config-id');
      var btnSalvarCfg = q(scope, 'btn-salvar-config');

      btnTestar && btnTestar.addEventListener('click', function () {
        var configId = getConfigId(scope);
        if (!configId) return updateLog(scope, 'Salve ou selecione uma configuração ADP antes do teste.');

        updateLog(scope, 'Testando conexão ADP...');
        getJson('/adp/discovery/status?integrador_config_id=' + encodeURIComponent(configId))
          .then(function (res) {
            updateLog(scope, (res.success ? 'Conexão ADP OK.' : 'Falha na conexão ADP.') + ' ' + (res.message || ''));
          })
          .catch(function (err) {
            updateLog(scope, 'Erro ao testar conexão: ' + err.message);
          });
      });

      btnBuscar && btnBuscar.addEventListener('click', function(){ loadDevices(scope); });

      btnSync && btnSync.addEventListener('click', function () {
        var configId = getConfigId(scope);
        if (!configId) return updateLog(scope, 'Salve ou selecione uma configuração ADP antes da sincronização.');

        updateLog(scope, 'Sincronizando dispositivos ADP no banco local...');
        getJson('/adp/discovery/sync-devices?integrador_config_id=' + encodeURIComponent(configId), {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
          }
        }).then(function (res) {
          updateLog(scope, (res.success ? 'Sync concluído.' : 'Sync com falha.') + ' ' + (res.devices_synced || 0) + ' dispositivos sincronizados.');
        }).catch(function (err) {
          updateLog(scope, 'Erro ao sincronizar: ' + err.message);
        });
      });

      scaleSelect && scaleSelect.addEventListener('change', function () {
        var scaleUuidInput = q(scope, 'scale-uuid');
        if (scaleUuidInput) scaleUuidInput.value = scaleSelect.value;
      });

      cameraSelect && cameraSelect.addEventListener('change', function(){ syncDerivedFields(scope); });
      configSelect && configSelect.addEventListener('change', function(){ fillConfigEditorFromSelect(scope); });
      btnSalvarCfg && btnSalvarCfg.addEventListener('click', function(){ salvarConfigAdp(scope); });
      loadConfigs(scope);
    });
  });
})();
