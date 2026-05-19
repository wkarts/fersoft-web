window.AdpDeviceDiscovery = {
  async list(baseUrl, token = '') {
    const url = `/adp/discovery/devices?base_url=${encodeURIComponent(baseUrl)}&global_token=${encodeURIComponent(token)}&global_token_type=x_adp_api_token`;
    const r = await fetch(url);
    return r.json();
  }
};
