// Recebe o endereço do backend passado pelo Blade
const backendURL = window.backendURL || "http://127.0.0.1:3333";

// Elementos do DOM
const backEl = document.querySelector("#back");
const menuConfigEl = document.querySelector("#menu-config");
const saveEl = document.querySelector("#save");
const mainDivEl = document.querySelector("#principal");
const configDivEl = document.querySelector("#config");
const selectPortEl = document.querySelector("#port-list");
const selectEquipEl = document.querySelector("#equip-list");

// Botão Voltar
backEl.onclick = () => {
    mainDivEl.style.display = 'block';
    configDivEl.style.display = "none";
    cleanSelect(); // Limpa as seleções ao voltar
}

// Botão Configurar
menuConfigEl.onclick = () => {
    mainDivEl.style.display = 'none';
    configDivEl.style.display = "block";
    loadSelect(); // Carrega as opções das portas e equipamentos
}

// Botão Salvar
saveEl.onclick = async () => {
    await saveConfig(); // Salva as configurações
}

// Função para carregar as configurações salvas e preencher a lista
async function loadSelect() {
    try {
        let { data: { portsAvailable, suportedEquipaments } } = await axios.get(`${backendURL}/api/configinfo`);

        // Limpar listas
        selectPortEl.innerHTML = "";
        selectEquipEl.innerHTML = "";

        // Recuperar configurações salvas
        const portaSalva = localStorage.getItem('porta');
        const equipamentoSalvo = localStorage.getItem('equipamento');

        // Preencher portas
        portsAvailable.forEach(data => {
            let option = document.createElement("option");
            option.value = data;
            option.innerHTML = data;
            if (data === portaSalva) option.selected = true; // Selecionar porta salva
            selectPortEl.appendChild(option);
        });

        // Preencher equipamentos
        suportedEquipaments.forEach(data => {
            let option = document.createElement("option");
            option.value = data;
            option.innerHTML = data;
            if (data === equipamentoSalvo) option.selected = true; // Selecionar equipamento salvo
            selectEquipEl.appendChild(option);
        });

        console.log(portsAvailable);
        console.log(suportedEquipaments);
    } catch (error) {
        console.error("Erro ao carregar portas e equipamentos: ", error);
        alert("Erro ao carregar portas e modelos. Verifique o servidor.");
    }
}

// Salvar configurações
async function saveConfig() {
    await axios.get(`${backendURL}/api/close`); // Fecha a porta antes de alterar

    let porta = selectPortEl.value;
    let equipamento = selectEquipEl.value;

    // Salvar no localStorage
    localStorage.setItem('porta', porta);
    localStorage.setItem('equipamento', equipamento);

    // Configurar no backend
    await axios.get(`${backendURL}/api/open?port=${porta}`);
    alert("Configurações salvas!");
}

// Limpar seleções
function cleanSelect() {
    selectPortEl.innerHTML = "";
    selectEquipEl.innerHTML = "";
}
