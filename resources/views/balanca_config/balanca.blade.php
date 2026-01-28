@extends('default.layout')

@section('content')

    <style type="text/css">
        #menu-listar-portas{
            visibility: hidden;
        }

        #menu-config{
            visibility: hidden;
        }

        #principal .botoes{
            transform:translatex(4px) translatey(369px);
        }

        #principal{
            width:390px !important;
            transform:translatex(-77px) translatey(0px);
        }

        #balanca-select{
            width:78%;
            min-height:52px;
            transform:translatex(52px) translatey(-5px);
        }

        /* Botoes */
        #principal .botoes{
            padding-bottom:14px;
            transform:translatex(3px) translatey(356px) !important;
            background-color:#7f8c8d;
            padding-top:13px;
            position:relative;
            left:-3px;
            top:7px;
        }

        /* Connect */
        #connect{
            position:relative;
            left:-49px;
        }

        /* Disconnect */
        #disconnect{
            position:relative;
            left:41px;
        }

        #principal{
            border-top-left-radius:10px;
            border-top-right-radius:10px;
            border-bottom-left-radius:10px;
            border-bottom-right-radius:10px;
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/balanca-style.css') }}">

    <!-- Configurações passadas pelo Laravel -->
    <script>
        window.balancaConfig = @json($balancaSelecionada ?? []);
    </script>

    <!-- Aplicativo -->
    <div id="app">
        <!-- Seleção da Balança -->
        <div class="form-group">
            <label for="balanca-select">Balança - Conferência de Pesagens:</label>
            <select id="balanca-select" class="form-control">
                <option value="">Selecione uma balança</option>
                @foreach($balancas as $balanca)
                    <option value="{{ $balanca->id }}"
                            data-backend="{{ $balanca->backend_server_address }}"
                            data-modelo="{{ $balanca->modelo }}"
                            data-port="{{ $balanca->port }}">
                        {{ $balanca->descricao }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Tela Principal -->
        <div id="principal">
            <div class="botoes">
                <button id="connect" disabled>Conectar</button>
                <button id="disconnect" disabled>Desconectar</button>
            </div>
            <div id="p1">
                <h4>Peso Bruto (Kg): </h4><span id="pb">----</span>
            </div>
            <div id="p2">
                <h4>Tara (Kg): </h4><span id="tara">----</span>
            </div>
            <div id="p3">
                <h4>Peso Líquido (Kg): </h4><span id="pl">----</span>
            </div>
            <p id="estabilidade">----</p>
            <p id="msgserial"></p>
        </div>
    </div>
@endsection

@section('javascript')
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script>
        let balancaSelecionada = null;
        let getDataInterval;

        // Selecionar Balança
        document.getElementById('balanca-select').addEventListener('change', function () {
            const select = this.options[this.selectedIndex];
            const id = select.value;
            const backend = select.getAttribute('data-backend');
            const modelo = select.getAttribute('data-modelo');
            const porta = select.getAttribute('data-port');

            if (id) {
                balancaSelecionada = { id, backend, modelo, porta };
                console.log(`Balança selecionada: ${modelo} (${backend})`);
                document.getElementById('connect').disabled = false; // Habilita botão conectar
                document.getElementById('disconnect').disabled = true; // Desabilita botão desconectar
            } else {
                balancaSelecionada = null;
                console.log('Nenhuma balança selecionada.');
                document.getElementById('connect').disabled = true;
                document.getElementById('disconnect').disabled = true;
            }
        });

        // Conectar
        document.getElementById('connect').addEventListener('click', async () => {
            if (!balancaSelecionada) {
                alert("Selecione uma balança antes de conectar.");
                return;
            }

            const url = `${balancaSelecionada.backend}/api/open?port=${balancaSelecionada.porta}`;
            console.log("Tentando conectar à URL:", url);

            try {
                const response = await axios.get(url);
                console.log("Resposta ao conectar:", response.data);

                if (!response.data.status.error) {
                    console.log(`Conectado à balança ${balancaSelecionada.modelo}`);
                    iniciarLeitura();
                } else if (response.data.status.messageText.includes('porta aberta')) {
                    console.log('A porta já está aberta. Mantendo conectado.');
                    iniciarLeitura(); // Continua leitura mesmo se a porta já estiver aberta
                } else {
                    alert(`Erro ao conectar: ${response.data.status.messageText}`);
                }

                // Ajusta os botões
                document.getElementById('connect').disabled = true;
                document.getElementById('disconnect').disabled = false; // Habilita desconectar
            } catch (error) {
                console.error('Erro ao conectar:', error);
                alert(`Erro ao conectar: ${error.message}`);
            }
        });

        // Desconectar
        document.getElementById('disconnect').addEventListener('click', async () => {
            if (!balancaSelecionada) {
                alert("Selecione uma balança antes de desconectar.");
                return;
            }

            const url = `${balancaSelecionada.backend}/api/close`;
            console.log("Tentando desconectar:", url);

            try {
                await axios.get(url);
                clearInterval(getDataInterval);
                console.log("Desconectado.");

                // Ajusta os botões
                document.getElementById('connect').disabled = false; // Habilita conectar
                document.getElementById('disconnect').disabled = true; // Desabilita desconectar
            } catch (error) {
                console.error('Erro ao desconectar:', error);
                alert(`Erro ao desconectar: ${error.message}`);
            }
        });

        // Iniciar Leitura Dinâmica (atualização otimizada)
        async function iniciarLeitura() {
            // Taxa de atualização otimizada: 200ms
            getDataInterval = setInterval(async () => {
                const url = `${balancaSelecionada.backend}/api/data?equip=${balancaSelecionada.modelo}`;
                console.log("Lendo dados:", url);

                try {
                    const response = await axios.get(url);
                    const data = response.data;

                    if (!data.status.error) {
                        document.getElementById('pb').innerText = data.data.peso_bruto;
                        document.getElementById('tara').innerText = data.data.tara;
                        document.getElementById('pl').innerText = data.data.peso_liq;
                        document.getElementById('estabilidade').innerText = data.data.estavel ? "Estável" : "Oscilando";

                        if (data.data.sobrecarga) {
                            document.getElementById('estabilidade').innerText = "Sobrecarga";
                        }
                    } else {
                        console.error("Erro ao ler dados:", data.status.messageText);
                    }
                } catch (error) {
                    console.error('Erro na leitura automática:', error);
                }
            }, 200); // Atualiza a cada 200ms
        }
    </script>
@endsection
