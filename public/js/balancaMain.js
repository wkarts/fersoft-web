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

// Inicializar a aplicação
start();

async function start() {
    disconnectEl.disabled = true;
    connectEl.disabled = false;

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
connectEl.onclick = async () => {
    if (!config.port) {
        messageSerial("Nenhuma porta configurada!", "red");
        return;
    }

    let response = await axios.get(`${backendURLL}/api/open?port=${config.port}`);
    disconnectEl.disabled = !response.data.status.error;
    connectEl.disabled = response.data.status.error;
};

// Desconectar
disconnectEl.onclick = async () => {
    let response = await axios.get(`${backendURLL}/api/close`);
    disconnectEl.disabled = !response.data.status.error;
    connectEl.disabled = response.data.status.error;
};

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
            pbElement.innerHTML = response.data.data.peso_bruto;
            taraElement.innerHTML = response.data.data.tara;
            plElement.innerHTML = response.data.data.peso_liq;
            estabilidadeEl.innerHTML = response.data.data.estavel ? "Estável" : "Oscilando";
            if (response.data.data.sobrecarga) {
                estabilidadeEl.innerHTML = "Sobrecarga";
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
    disconnectEl.disabled = !statusPort;
    connectEl.disabled = statusPort;
}

// Mensagens para o usuário
function messageSerial(message, color = "blue") {
    msgSerialEl.style.color = color;
    msgSerialEl.innerHTML = message;
}

// LISTAR PORTAS - Integrado ao endpoint
listarPortasEl.onclick = async () => {
    try {
        const response = await axios.get(`${backendURLL}/api/configinfo`);
        const formattedData = JSON.stringify(response.data, null, 4);
        portasContentEl.textContent = formattedData;

        document.getElementById('principal').style.display = 'none';
        listarPortasDivEl.style.display = 'block';
    } catch (error) {
        portasContentEl.textContent = "Erro ao carregar dados!";
    }
};

// Botão voltar do listar portas
backPortasEl.onclick = () => {
    listarPortasDivEl.style.display = 'none';
    document.getElementById('principal').style.display = 'block';
};

// **Função para verificar o status de uma balança**
async function verificarStatusBalanca(id, backendURL, equipamento) {
    const statusLed = document.getElementById(`status-led-${id}`);
    if (!statusLed) return;

    try {
        let response = await axios.get(`${backendURL}/api/data?equip=${equipamento}`);
        const status = response.data.status;

        // Status Verde (Online e funcionando)
        if (status.portOpened && status.messageText === "Conexão funcionando corretamente" && !status.error) {
            statusLed.style.backgroundColor = 'green';
            statusLed.title = "Balança Online e Funcionando!";
        }
        // Status Laranja (Sem transmissão)
        else if (status.portOpened && status.messageText === "Conectado. Sem transmissão de dados" && status.error) {
            statusLed.style.backgroundColor = 'orange';
            statusLed.title = "Balança Online, mas Sem Transmissão!";
        }
        // Status Branco (Porta fechada)
        else if (!status.portOpened && status.messageText === "Sem conexão com a porta serial" && status.error) {
            statusLed.style.backgroundColor = 'white';
            statusLed.title = "Balança Offline ou Porta Fechada!";
        } else {
            // Status Vermelho (Erro no Backend)
            statusLed.style.backgroundColor = 'red';
            statusLed.title = "Erro no Backend!";
        }
    } catch (error) {
        console.error(`Erro ao verificar status da balança ${id}:`, error);
        if (statusLed) {
            statusLed.style.backgroundColor = 'red';
            statusLed.title = "Backend Offline!";
        }
    }
}

// Função para verificar todas as balanças
function verificarTodasAsBalancas() {
    balancas.forEach(balanca => {
        verificarStatusBalanca(balanca.id, balanca.backend_server_address, balanca.modelo);
    });
}

// Atualiza status a cada 10 segundos
setInterval(verificarTodasAsBalancas, 10000);
verificarTodasAsBalancas(); // Primeira execução
