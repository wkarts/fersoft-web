document.addEventListener('DOMContentLoaded', function () {

    // Função para listar portas
    document.querySelectorAll('[id^="listarPortas"]').forEach(botaoListar => {
        botaoListar.addEventListener('click', async function () {
            const modal = botaoListar.closest('.modal');
            const portaSelect = modal.querySelector('.porta-select');
            const portaSalva = portaSelect.getAttribute('data-port-salva'); // Porta salva
            const customPortInput = modal.querySelector('.custom-port');

            try {
                const response = await fetch('/balancas/listarPortas', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (data.ports && data.ports.length > 0) {
                    portaSelect.innerHTML = '<option value="">Selecione</option>';

                    // Lista portas disponíveis
                    data.ports.forEach(port => {
                        const option = document.createElement('option');
                        option.value = port;
                        option.textContent = port;

                        // Marca como selecionado se for igual ao valor salvo
                        if (port === portaSalva) {
                            option.selected = true;
                        }

                        portaSelect.appendChild(option);
                    });

                    // Se houver valor customizado, mantêm preenchido
                    if (customPortInput && customPortInput.value) {
                        const optionCustom = document.createElement('option');
                        optionCustom.value = customPortInput.value;
                        optionCustom.textContent = customPortInput.value;
                        optionCustom.selected = true;

                        portaSelect.appendChild(optionCustom);
                    }

                    alert('Portas listadas com sucesso!');
                } else {
                    alert('Nenhuma porta encontrada!');
                }
            } catch (error) {
                console.error('Erro ao listar portas:', error);
                alert('Erro ao listar portas. Verifique a conexão.');
            }
        });
    });

    // Conectar e testar a balança
    document.querySelectorAll('[id^="conectarBalanca"]').forEach(botaoConectar => {
        botaoConectar.addEventListener('click', async function () {
            const modal = botaoConectar.closest('.modal');
            const portaSelect = modal.querySelector('.porta-select');
            const portaSelecionada = portaSelect.value || modal.querySelector('.custom-port').value;N

            if (!portaSelecionada) {
                alert('Selecione ou digite uma porta antes de conectar!');
                return;
            }

            try {
                const response = await fetch('/balancas/testarBalanca', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ porta: portaSelecionada })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Peso lido: ' + data.peso);
                } else {
                    alert('Erro ao conectar: ' + data.error);
                }
            } catch (error) {
                console.error('Erro ao conectar na balança:', error);
                alert('Erro ao conectar na balança. Verifique a porta selecionada.');
            }
        });
    });

    // Alterna o uso de porta personalizada
    document.querySelectorAll('.custom-port-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const modal = checkbox.closest('.modal');
            const customPortInput = modal.querySelector('.custom-port');

            if (checkbox.checked) {
                customPortInput.classList.remove('d-none');
            } else {
                customPortInput.classList.add('d-none');
                customPortInput.value = ''; // Limpa o valor se desativado
            }
        });
    });

    // Atualiza automaticamente a porta salva ao abrir o modal
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
            const portaSelect = modal.querySelector('.porta-select');
            const portaSalva = portaSelect.getAttribute('data-port-salva');
            const customPort = modal.querySelector('.custom-port');
            const customPortCheckbox = modal.querySelector('.custom-port-checkbox');

            // Porta personalizada
            if (customPort && customPort.value) {
                customPortCheckbox.checked = true;
                customPort.classList.remove('d-none');
            }

            // Porta salva padrão
            if (portaSalva) {
                const optionExistente = portaSelect.querySelector(`option[value="${portaSalva}"]`);

                if (!optionExistente) {
                    const option = document.createElement('option');
                    option.value = portaSalva;
                    option.textContent = portaSalva;
                    option.selected = true;

                    portaSelect.appendChild(option);
                } else {
                    optionExistente.selected = true;
                }
            }
        });
    });
});
