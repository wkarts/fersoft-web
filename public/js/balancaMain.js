// Monitoramento das balanças cadastradas via ADP.
// O antigo fluxo serial local em PHP foi removido.

const balancas = (window.balancas && Array.isArray(window.balancas.data))
    ? window.balancas.data
    : [];

const adpRuntimeConfigCache = new Map();

function csrfTokenBalanca() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

async function fetchJsonBalanca(url, options = {}) {
    const headers = Object.assign({
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }, options.headers || {});

    const response = await fetch(url, Object.assign({}, options, { headers }));
    let data = null;

    try {
        data = await response.json();
    } catch (e) {
        data = null;
    }

    if (!response.ok) {
        const message = data && (data.message || data.error)
            ? (data.message || data.error)
            : `HTTP ${response.status}`;
        throw new Error(message);
    }

    return data || {};
}

function appendApiPath(baseUrl, path) {
    const base = String(baseUrl || '').replace(/\/+$/, '');
    const cleanPath = '/' + String(path || '').replace(/^\/+/, '');

    if (base.endsWith('/api') && cleanPath.startsWith('/api/')) {
        return base + cleanPath.substring(4);
    }

    return base + cleanPath;
}

function buildAdpHeaders(config) {
    const headers = { 'Accept': 'application/json' };
    const type = config.global_token_type || 'x_adp_api_token';
    const token = config.global_token || '';
    const header = config.global_token_header || 'X-ADP-API-TOKEN';

    if (!token || type === 'none') {
        return headers;
    }

    if (type === 'bearer') {
        headers.Authorization = `Bearer ${token}`;
        return headers;
    }

    if (type === 'x_adp_api_token') {
        headers[header] = token;
        return headers;
    }

    return headers;
}

function appendAdpQueryToken(url, config) {
    const type = config.global_token_type || 'x_adp_api_token';
    const token = config.global_token || '';

    if (type !== 'query' || !token) {
        return url;
    }

    return url + (url.includes('?') ? '&' : '?') + 'token=' + encodeURIComponent(token);
}

async function getAdpRuntimeConfig(integradorConfigId, baseUrl) {
    const id = String(integradorConfigId || '').trim();
    const base = String(baseUrl || '').trim().replace(/\/+$/, '');

    if (!id && !base) {
        throw new Error('Configuração ADP não vinculada à balança.');
    }

    const cacheKey = id ? `id:${id}|base:${base}` : `base:${base}`;
    if (adpRuntimeConfigCache.has(cacheKey)) {
        return adpRuntimeConfigCache.get(cacheKey);
    }

    const params = new URLSearchParams();
    if (id) params.set('integrador_config_id', id);
    if (base) params.set('base_url', base);

    const data = await fetchJsonBalanca(`/adp/discovery/configs/runtime?${params.toString()}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfTokenBalanca()
        }
    });

    if (!data.success || !data.config) {
        throw new Error(data.message || 'Configuração ADP inválida.');
    }

    adpRuntimeConfigCache.set(cacheKey, data.config);
    if (data.config.id) {
        adpRuntimeConfigCache.set(
            `id:${data.config.id}|base:${String(data.config.base_url || '').replace(/\/+$/, '')}`,
            data.config
        );
    }

    return data.config;
}

function normalizeAdpScaleStatus(payload) {
    const health = payload.health || {};
    const status = payload.status || {};
    const data = payload.data || payload.weight || null;

    const connected = Boolean(
        health.connected ??
        status.port_opened ??
        status.portOpened ??
        false
    );

    const receivingData = Boolean(
        health.receiving_data ??
        health.receivingData ??
        false
    );

    const hasData = Boolean(data);
    const error = Boolean(status.error ?? payload.error ?? false);
    const message = String(
        status.message_text ||
        status.messageText ||
        payload.message ||
        health.last_error ||
        ''
    );

    if (connected && (hasData || receivingData) && !error) {
        return { color: 'green', title: 'Balança Online e Funcionando!' };
    }

    if (connected && !hasData) {
        return { color: 'orange', title: 'Balança Online, mas sem transmissão de dados.' };
    }

    if (!connected && !payload.error) {
        return { color: 'white', title: message || 'Balança Offline ou Driver Fechado.' };
    }

    return { color: 'red', title: message || 'Erro no backend ADP.' };
}

function parseAdpUuidList(value) {
    if (window.AdpRuntimeClient && typeof window.AdpRuntimeClient.parseUuidList === 'function') {
        return window.AdpRuntimeClient.parseUuidList(value || []);
    }

    if (Array.isArray(value)) {
        return value.map(String).map((item) => item.trim()).filter(Boolean);
    }

    const raw = String(value || '').trim();
    if (!raw) {
        return [];
    }

    try {
        const decoded = JSON.parse(raw);
        return parseAdpUuidList(decoded);
    } catch (e) {
        return raw.split(',').map((item) => item.trim()).filter(Boolean);
    }
}

async function verificarStatusBalancaAdp(balanca, statusLed) {
    const config = await getAdpRuntimeConfig(
        balanca.integrador_config_id,
        balanca.backend_server_address || balanca.backend
    );
    const uuid = String(balanca.adp_scale_uuid || '').trim();

    if (!uuid) {
        statusLed.style.backgroundColor = 'white';
        statusLed.title = 'Balança ADP sem UUID vinculado.';
        return;
    }

    let url = appendApiPath(config.base_url, `/api/scales/${encodeURIComponent(uuid)}/data`);
    url = appendAdpQueryToken(url, config);

    const payload = await fetchJsonBalanca(url, {
        method: 'GET',
        headers: buildAdpHeaders(config)
    });

    const normalized = normalizeAdpScaleStatus(payload);
    statusLed.style.backgroundColor = normalized.color;
    statusLed.title = normalized.title;
}

async function verificarStatusCamerasAdp(balanca) {
    const summary = document.querySelector(`[data-adp-camera-health-summary="${balanca.id}"]`);
    if (!summary) return;

    const uuids = parseAdpUuidList(balanca.adp_camera_uuids || []);

    if (!uuids.length) {
        summary.textContent = 'Sem câmeras';
        summary.className = 'text-muted';
        return;
    }

    try {
        const config = await getAdpRuntimeConfig(
            balanca.integrador_config_id,
            balanca.backend_server_address || balanca.backend
        );
        let online = 0;

        for (const uuid of uuids) {
            let url = appendApiPath(config.base_url, `/api/cameras/${encodeURIComponent(uuid)}/health`);
            url = appendAdpQueryToken(url, config);
            const payload = await fetchJsonBalanca(url, {
                method: 'GET',
                headers: buildAdpHeaders(config)
            });

            const ok = payload.success !== false && payload.error !== true && payload.result?.success !== false;
            if (ok) online++;
        }

        summary.textContent = `${online}/${uuids.length} online`;
        summary.className = online === uuids.length
            ? 'text-success'
            : (online > 0 ? 'text-warning' : 'text-danger');
    } catch (e) {
        summary.textContent = 'Erro câmera';
        summary.className = 'text-danger';
    }
}

function normalizeBalancaArgs(arg1) {
    if (arg1 && typeof arg1 === 'object') {
        return arg1;
    }

    const statusLed = document.getElementById(`status-led-${arg1}`);

    return {
        id: arg1,
        backend_server_address: statusLed ? statusLed.dataset.backend : '',
        integrador: statusLed ? statusLed.dataset.integrador : 'adp',
        integrador_config_id: statusLed ? statusLed.dataset.integradorConfigId : '',
        adp_scale_uuid: statusLed ? statusLed.dataset.adpScaleUuid : '',
        adp_camera_uuids: statusLed ? (statusLed.dataset.adpCameraUuids || '[]') : '[]'
    };
}

async function verificarStatusBalanca(arg1) {
    const balanca = normalizeBalancaArgs(arg1);
    const statusLed = document.getElementById(`status-led-${balanca.id}`);

    if (!statusLed) return;

    try {
        await verificarStatusBalancaAdp(balanca, statusLed);
        await verificarStatusCamerasAdp(balanca);
    } catch (error) {
        console.error(`Erro ao verificar status da balança ${balanca.id}:`, error);
        statusLed.style.backgroundColor = 'red';
        statusLed.title = error && error.message ? error.message : 'Backend ADP offline.';
    }
}

function verificarTodasAsBalancas() {
    balancas.forEach((balanca) => verificarStatusBalanca(balanca));
}

window.verificarStatusBalanca = verificarStatusBalanca;
window.verificarTodasAsBalancas = verificarTodasAsBalancas;

if (balancas.length) {
    verificarTodasAsBalancas();
    setInterval(verificarTodasAsBalancas, 10000);
}
