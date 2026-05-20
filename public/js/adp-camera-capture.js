(function () {
  'use strict';

  function imageFromSnapshot(response) {
    const value = response?.image_url || response?.snapshot_url || response?.url || response?.data?.image_url || response?.result?.image_url || response?.base64 || response?.data?.base64 || response?.result?.base64 || '';
    if (!value) return '';
    if (String(value).startsWith('data:image')) return value;
    if (String(value).length > 200 && !String(value).startsWith('http')) return 'data:image/jpeg;base64,' + value;
    return value;
  }

  async function capture(scope) {
    const select = scope.querySelector('[data-adp-camera-capture="camera"]');
    const option = select?.selectedOptions?.[0];
    if (!option || !option.value) {
      alert('Selecione uma câmera ADP.');
      return;
    }
    const cfg = await window.AdpRuntimeClient.getRuntimeConfig(option.dataset.integradorConfigId);
    const res = await window.AdpRuntimeClient.adpRequest(cfg, '/api/cameras/' + encodeURIComponent(option.dataset.cameraUuid) + '/snapshot', 'POST', {});
    const image = imageFromSnapshot(res);
    const preview = scope.querySelector('[data-adp-camera-capture="preview"]');
    const empty = scope.querySelector('[data-adp-camera-capture="empty"]');
    const value = scope.querySelector('[data-adp-camera-capture="value"]');
    const target = scope.dataset.targetInput ? document.querySelector(scope.dataset.targetInput) : null;

    if (image && preview) {
      preview.src = image;
      preview.style.display = '';
      if (empty) empty.style.display = 'none';
      if (value) value.value = image;
      if (target) target.value = image;
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.adp-camera-capture').forEach(function (scope) {
      const btn = scope.querySelector('[data-adp-camera-capture="btn"]');
      btn && btn.addEventListener('click', function () {
        capture(scope).catch(function (e) { alert(e.message || 'Erro ao capturar imagem.'); });
      });
    });
  });
})();
