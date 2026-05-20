// Configuração inicial
const backendURLL = window.backendURL || "https://127.0.0.1:3333";

// Carregar balanças do Blade
const balancas = (window.balancas && Array.isArray(window.balancas.data))
    ? window.balancas.data
    : [];

// Elementos do DOM
const pbElement = document.querySelector("#pb");
const taraElement = document.querySelector("#tara");
const plElement = document.querySelector("#pl");
const msgSerialEl = document.querySelector("#msgserial");
const connectEl = document.querySelector("#connect");
const disconnectEl = document.querySelector("#disconnect");
const estabilidadeEl = document.querySelector("#estabilidade");
const listarPortasEl = document.querySelector("#menu-listar-portas");
const portasContentEl = document.querySelector("#portas-content");
const listarPortasDivEl = document.querySelector("#listar-portas");
const backPortasEl = document.querySelector("#back-portas");

// Variáveis globais
let getDataInterval, config;

function hasLegacyBalancaPanel() {
    return !!(connectEl && disconnectEl && msgSerialEl);
}

function setDisabled(el, disabled) {
    if (el) {
        el.disabled = disabled;
    }
}

function setHtml(el, value) {
    if (el) {
        el.innerHTML = value;
    }
}

function setText(el, value) {
    if (el) {
        el.textContent = value;
    }
}

function setDisplay(el, value) {
    if (el) {
        el.style.display = value;
    }
}

// Inicializar a aplicação
start();

async function start() {
    // Este JS é carregado em mais de uma tela. Se os elementos do painel legado
    // não existirem, não deve quebrar a tela atual.
    if (!hasLegacyBalancaPanel()) {
        return;
    }

    setDisabled(disconnectEl, true);
    setDisabled(connectEl, false);

    // Recuperar configurações salvas
    const porta = localStorage.getItem('porta') || "";
    const equipamento = localStorage.getItem('equipamento') || "";

    // Configurar variáveis globais
    config = { port: porta, equipament: equipamento };
    console.log("Configuração carregada:", config); // Debug

    // Validação
    if (!config.port || !config.equipament) {
        messageSerial("Configuração incompleta!", "red");
        return;
    }

    await obterDadosAPI();
    getDataInterval = setInterval(obterDadosAPI, 500);
}

// Conectar
if (connectEl) {
    connectEl.onclick = async () => {
        if (!config || !config.port) {
            messageSerial("Nenhuma porta configurada!", "red");
            return;
        }

        let response = await axios.get(`${backendURLL}/api/open?port=${config.port}`);
        setDisabled(disconnectEl, !response.data.status.error);
        setDisabled(connectEl, response.data.status.error);
    };
}

// Desconectar
if (disconnectEl) {
    disconnectEl.onclick = async () => {
        let response = await axios.get(`${backendURLL}/api/close`);
        setDisabled(disconnectEl, !response.data.status.error);
        setDisabled(connectEl, response.data.status.error);
    };
}

// Obter dados da balança
async function obterDadosAPI() {
    try {
        // Gera URL dinamicamente
        let apiUrl = `${backendURLL}/api/data?equip=${config.equipament}`;
        console.log("URL gerada:", apiUrl); // Debug URL

        let response = await axios.get(apiUrl);

        if (response.data.status.error) {
            messageSerial(response.data.status.messageText, "red");
            portOpened(response.data.status.portOpened);
        } else {
            setHtml(pbElement, response.data.data.peso_bruto);
            setHtml(taraElement, response.data.data.tara);
            setHtml(plElement, response.data.data.peso_liq);
            setHtml(estabilidadeEl, response.data.data.estavel ? "Estável" : "Oscilando");
            if (response.data.data.sobrecarga) {
                setHtml(estabilidadeEl, "Sobrecarga");
            }
            messageSerial(response.data.status.messageText, "green");
            portOpened(response.data.status.portOpened);
        }
    } catch (error) {
        console.error("Erro ao obter dados:", error);
    }
}

// Controle do botão de conexão
function portOpened(statusPort) {
    setDisabled(disconnectEl, !statusPort);
    setDisabled(connectEl, statusPort);
}

// Mensagens para o usuário
function messageSerial(message, color = "blue") {
    if (!msgSerialEl) {
        return;
    }

    msgSerialEl.style.color = color;
    msgSerialEl.innerHTML = message;
}

// LISTAR PORTAS - Integrado ao endpoint
if (listarPortasEl) {
    listarPortasEl.onclick = async () => {
        try {
            const response = await axios.get(`${backendURLL}/api/configinfo`);
            const formattedData = JSON.stringify(response.data, null, 4);
            setText(portasContentEl, formattedData);

            setDisplay(document.getElementById('principal'), 'none');
            setDisplay(listarPortasDivEl, 'block');
        } catch (error) {
            setText(portasContentEl, "Erro ao carregar dados!");
        }
    };
}

// Botão voltar do listar portas
if (backPortasEl) {
    backPortasEl.onclick = () => {
        setDisplay(listarPortasDivEl, 'none');
        setDisplay(document.getElementById('principal'), 'block');
    };
}

// Cache das configurações ADP usadas pela listagem.
// O token global não fica exposto no HTML; é obtido sob demanda via rota autenticada do Laravel.
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

async function getAdpRuntimeConfig(integradorConfigId) {
    const id = String(integradorConfigId || '').trim();

    if (!id) {
        throw new Error('Configuração ADP não vinculada à balança.');
    }

    if (adpRuntimeConfigCache.has(id)) {
        return adpRuntimeConfigCache.get(id);
    }

    const url = `/adp/discovery/configs/runtime?integrador_config_id=${encodeURIComponent(id)}`;
    const data = await fetchJsonBalanca(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': csrfTokenBalanca()
        }
    });

    if (!data.success || !data.config) {
        throw new Error(data.message || 'Configuração ADP inválida.');
    }

    adpRuntimeConfigCache.set(id, data.config);
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
        return {
            color: 'green',
            title: 'Balança Online e Funcionando!'
        };
    }

    if (connected && !hasData) {
        return {
            color: 'orange',
            title: 'Balança Online, mas sem transmissão de dados.'
        };
    }

    if (!connected && !payload.error) {
        return {
            color: 'white',
            title: message || 'Balança Offline ou Porta Fechada.'
        };
    }

    return {
        color: 'red',
        title: message || 'Erro no backend ADP.'
    };
}

async function verificarStatusBalancaAdp(balanca, statusLed) {
    const config = await getAdpRuntimeConfig(balanca.integrador_config_id);
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

async function verificarStatusBalancaLegacy(id, backendURL, equipamento, statusLed) {
    let response = await axios.get(`${backendURL}/api/data?equip=${equipamento}`);
    const status = response.data.status;

    const portOpened = Boolean(status.portOpened ?? status.port_opened ?? false);
    const messageText = String(status.messageText || status.message_text || '');
    const error = Boolean(status.error ?? false);

    // Status Verde (Online e funcionando)
    if (portOpened && messageText === "Conexão funcionando corretamente" && !error) {
        statusLed.style.backgroundColor = 'green';
        statusLed.title = "Balança Online e Funcionando!";
    }
    // Status Laranja (Sem transmissão)
    else if (portOpened && messageText === "Conectado. Sem transmissão de dados" && error) {
        statusLed.style.backgroundColor = 'orange';
        statusLed.title = "Balança Online, mas Sem Transmissão!";
    }
    // Status Branco (Porta fechada)
    else if (!portOpened && messageText === "Sem conexão com a porta serial" && error) {
        statusLed.style.backgroundColor = 'white';
        statusLed.title = "Balança Offline ou Porta Fechada!";
    } else {
        // Status Vermelho (Erro no Backend)
        statusLed.style.backgroundColor = 'red';
        statusLed.title = "Erro no Backend!";
    }
}

function normalizeBalancaArgs(arg1, backendURL, equipamento) {
    if (arg1 && typeof arg1 === 'object') {
        return arg1;
    }

    const statusLed = document.getElementById(`status-led-${arg1}`);

    return {
        id: arg1,
        backend_server_address: backendURL || (statusLed ? statusLed.dataset.backend : ''),
        modelo: equipamento || (statusLed ? statusLed.dataset.equip : ''),
        integrador: statusLed ? statusLed.dataset.integrador : 'legacy',
        integrador_config_id: statusLed ? statusLed.dataset.integradorConfigId : '',
        adp_scale_uuid: statusLed ? statusLed.dataset.adpScaleUuid : ''
    };
}

// **Função para verificar o status de uma balança**
async function verificarStatusBalanca(arg1, backendURL, equipamento) {
    const balanca = normalizeBalancaArgs(arg1, backendURL, equipamento);
    const statusLed = document.getElementById(`status-led-${balanca.id}`);

    if (!statusLed) return;

    try {
        if ((balanca.integrador || 'legacy') === 'adp') {
            await verificarStatusBalancaAdp(balanca, statusLed);
            return;
        }

        await verificarStatusBalancaLegacy(
            balanca.id,
            balanca.backend_server_address,
            balanca.modelo,
            statusLed
        );
    } catch (error) {
        console.error(`Erro ao verificar status da balança ${balanca.id}:`, error);
        statusLed.style.backgroundColor = 'red';
        statusLed.title = error && error.message ? error.message : 'Backend Offline!';
    }
}

// Função para verificar todas as balanças
function verificarTodasAsBalancas() {
    balancas.forEach(balanca => {
        verificarStatusBalanca(balanca);
    });
}

// Atualiza status a cada 10 segundos
setInterval(verificarTodasAsBalancas, 10000);
verificarTodasAsBalancas(); // Primeira execução
