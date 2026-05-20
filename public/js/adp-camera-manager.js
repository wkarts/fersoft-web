(function () {
  'use strict';

  function setBadge(el, status, text) {
    if (!el) return;
    el.className = 'badge ' + (status === 'online' ? 'badge-success' : status === 'warning' ? 'badge-warning' : 'badge-danger');
    el.textContent = text;
  }

  function imageFromSnapshot(response) {
    const candidates = [
      response?.image_url,
      response?.snapshot_url,
      response?.url,
      response?.data?.image_url,
      response?.data?.snapshot_url,
      response?.result?.image_url,
      response?.result?.snapshot_url,
      response?.base64,
      response?.data?.base64,
      response?.result?.base64,
    ].filter(Boolean);

    const value = candidates[0] || '';
    if (!value) return '';
    if (String(value).startsWith('data:image')) return value;
    if (String(value).length > 200 && !String(value).startsWith('http')) return 'data:image/jpeg;base64,' + value;
    return value;
  }

  async function config(id) {
    return window.AdpRuntimeClient.getRuntimeConfig(id);
  }

  async function requestCamera(row, path, method, payload) {
    const configId = row.dataset.integradorConfigId;
    const cameraUuid = row.dataset.cameraUuid;
    const cfg = await config(configId);
    return window.AdpRuntimeClient.adpRequest(cfg, '/api/cameras/' + encodeURIComponent(cameraUuid) + path, method || 'GET', payload);
  }

  async function health(row) {
    const badge = document.querySelector('[data-camera-status="' + row.dataset.cameraRow + '"]');
    try {
      const res = await requestCamera(row, '/health', 'GET');
      const ok = res.success !== false && res.error !== true && (res.result?.success !== false);
      setBadge(badge, ok ? 'online' : 'warning', ok ? 'Online' : 'Atenção');
    } catch (e) {
      setBadge(badge, 'offline', 'Offline');
    }
  }

  async function snapshot(row) {
    const id = row.dataset.cameraRow;
    const img = document.querySelector('[data-camera-preview="' + id + '"]');
    const empty = document.querySelector('[data-camera-preview-empty="' + id + '"]');
    const badge = document.querySelector('[data-camera-status="' + id + '"]');
    try {
      const res = await requestCamera(row, '/snapshot', 'POST', {});
      const src = imageFromSnapshot(res);
      if (src && img) {
        img.src = src;
        img.style.display = '';
        if (empty) empty.style.display = 'none';
      }
      setBadge(badge, 'online', 'Online');
    } catch (e) {
      setBadge(badge, 'offline', 'Erro');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-camera-row]').forEach(function (row) {
      const id = row.dataset.cameraRow;
      const btnTest = document.querySelector('[data-camera-test="' + id + '"]');
      const btnSnapshot = document.querySelector('[data-camera-snapshot="' + id + '"]');

      btnTest && btnTest.addEventListener('click', function () { health(row); });
      btnSnapshot && btnSnapshot.addEventListener('click', function () { snapshot(row); });
    });
  });
})();
