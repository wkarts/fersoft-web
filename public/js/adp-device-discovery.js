window.AdpDeviceDiscovery = {
  async list(integradorConfigId) {
    const url = `/adp/discovery/devices?integrador_config_id=${encodeURIComponent(integradorConfigId)}`;
    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
    return r.json();
  }
};
