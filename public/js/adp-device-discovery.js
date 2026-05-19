(function () {
  function getJson(url, opts) {
    return fetch(url, Object.assign({ headers: { 'Accept': 'application/json' } }, opts || {})).then(function (r) {
      return r.json();
    });
  }

  function updateLog(text) {
    var log = document.getElementById('adp-discovery-log');
    if (!log) return;
    log.textContent = '[' + new Date().toISOString() + '] ' + text + "\n" + log.textContent;
  }

  function mapOption(device) {
    var name = device.name || device.uuid || 'sem nome';
    var status = device.status || 'unknown';
    return { value: device.uuid || '', label: name + ' (' + status + ')' };
  }

  function fillSelect(select, items, selectedValues) {
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
    return Array.from(select.selectedOptions || []).map(function (o) { return o.value; }).filter(Boolean);
  }

  function getConfigId() {
    var i = document.getElementById('integrador_config_id');
    return i ? String(i.value || '').trim() : '';
  }

  function syncDerivedFields() {
    var camSelect = document.getElementById('adp_camera_select');
    var selected = camSelect ? readSelectedValues(camSelect) : [];
    var csv = selected.join(',');

    var camerasInput = document.getElementById('adp_camera_uuids');
    var qtdInput = document.getElementById('quantidade_cameras');
    var usaCheck = document.getElementById('usa_cameras');

    if (camerasInput) camerasInput.value = csv;
    if (qtdInput) qtdInput.value = String(selected.length);
    if (usaCheck) usaCheck.checked = selected.length > 0;
  }

  function autoFillScaleFields(scale) {
    if (!scale) return;
    var mappings = {
      adp_scale_uuid: scale.uuid || '',
      porta_serial: scale.port || '',
      baud_rate: (scale.baud_rate || ''),
      integrador: 'adp'
    };

    Object.keys(mappings).forEach(function (id) {
      var input = document.getElementById(id);
      if (input) input.value = mappings[id];
    });
  }

  function loadDevices() {
    var configId = getConfigId();
    if (!configId) {
      updateLog('Informe integrador_config_id antes de buscar dispositivos.');
      return;
    }

    updateLog('Consultando dispositivos ADP...');
    return getJson('/adp/discovery/devices?integrador_config_id=' + encodeURIComponent(configId))
      .then(function (res) {
        if (!res.success) throw new Error(res.message || 'Falha na listagem de dispositivos');

        var devices = res.devices || [];
        var scales = devices.filter(function (d) { return d.type === 'scale'; });
        var cameras = devices.filter(function (d) { return d.type === 'camera'; });

        var scaleSelect = document.getElementById('adp_scale_select');
        var cameraSelect = document.getElementById('adp_camera_select');
        var currentScale = (document.getElementById('adp_scale_uuid') || {}).value || '';
        var currentCameras = ((document.getElementById('adp_camera_uuids') || {}).value || '').split(',').map(function(v){ return v.trim(); }).filter(Boolean);

        fillSelect(scaleSelect, scales.map(mapOption), [currentScale]);
        fillSelect(cameraSelect, cameras.map(mapOption), currentCameras);

        if (scaleSelect && scaleSelect.value) {
          var selected = scales.find(function (s) { return s.uuid === scaleSelect.value; });
          autoFillScaleFields(selected);
        }
        syncDerivedFields();

        updateLog('Discovery concluído. Balanças: ' + scales.length + ' | Câmeras: ' + cameras.length);
      })
      .catch(function (err) {
        updateLog('Erro no discovery: ' + err.message);
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('adp-cadastro-guided');
    if (!container) return;

    var btnTestar = document.getElementById('btn-adp-testar');
    var btnBuscar = document.getElementById('btn-adp-buscar');
    var btnSync = document.getElementById('btn-adp-sync');
    var scaleSelect = document.getElementById('adp_scale_select');
    var cameraSelect = document.getElementById('adp_camera_select');

    btnTestar && btnTestar.addEventListener('click', function () {
      var configId = getConfigId();
      if (!configId) return updateLog('Informe integrador_config_id para testar conexão.');

      updateLog('Testando conexão ADP...');
      getJson('/adp/discovery/status?integrador_config_id=' + encodeURIComponent(configId))
        .then(function (res) {
          updateLog((res.success ? 'Conexão ADP OK.' : 'Falha na conexão ADP.') + ' ' + (res.message || ''));
        })
        .catch(function (err) {
          updateLog('Erro ao testar conexão: ' + err.message);
        });
    });

    btnBuscar && btnBuscar.addEventListener('click', loadDevices);

    btnSync && btnSync.addEventListener('click', function () {
      var configId = getConfigId();
      if (!configId) return updateLog('Informe integrador_config_id para sincronizar.');

      updateLog('Sincronizando dispositivos ADP no banco local...');
      getJson('/adp/discovery/sync-devices?integrador_config_id=' + encodeURIComponent(configId), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
        }
      }).then(function (res) {
        updateLog((res.success ? 'Sync concluído.' : 'Sync com falha.') + ' ' + (res.devices_synced || 0) + ' dispositivos sincronizados.');
      }).catch(function (err) {
        updateLog('Erro ao sincronizar: ' + err.message);
      });
    });

    scaleSelect && scaleSelect.addEventListener('change', function () {
      var selectedText = scaleSelect.options[scaleSelect.selectedIndex] ? scaleSelect.options[scaleSelect.selectedIndex].text : '';
      var scaleUuidInput = document.getElementById('adp_scale_uuid');
      if (scaleUuidInput) scaleUuidInput.value = scaleSelect.value;
      updateLog('Balança selecionada: ' + selectedText);
    });

    cameraSelect && cameraSelect.addEventListener('change', syncDerivedFields);
  });
})();
