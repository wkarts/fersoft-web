@extends('default.layout')

@section('content')
    <style type="text/css">
        /* Altura fixa para linhas */
        .datatable-row, .datatable-cell {
            height: 35px !important;
            vertical-align: middle !important;
        }

        /* Células */
        .datatable-cell {
            text-align: center !important;
            padding: 5px !important;
            white-space: nowrap !important;
        }

        /* Cabeçalho fixo */
        .thead-light {
            position: sticky !important;
            top: 0 !important;
            z-index: 1 !important;
            background-color: white !important;
        }

        /* LED de status */
        .status-led {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid black;
        }

        /* LEDs menores (legenda) */
        .status-led-legenda {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid black;
        }

        /* Botões */
        .btn-custom {
            padding: 3px 8px !important;
            font-size: 12px !important;
            line-height: 1.2 !important;
        }

        /* Badges */
        .badge {
            padding: 5px !important;
            font-size: 12px !important;
            width: 100px !important;
            text-align: center;
        }

        /* Responsividade */
        .table-responsive {
            overflow-x: auto !important;
            overflow-y: auto !important;
        }

        /* ----- GRID DE BALANCAS ----- */

        .table-balancas-scroll {
            font-size: 12px !important; /* Tamanho menor para fontes */
            max-height: 450px !important; /* Altura máxima com scroll */
        }

        /* Cabeçalho fixo e responsivo */
        .thead-light {
            position: sticky !important;
            top: -1 !important; /* Fixação no topo */
            z-index: 1 !important; /* Prioridade sobre outros elementos */
            background-color: white !important; /* Fundo branco */
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Botão para abrir modal de nova balança -->
    <div class="card card-custom gutter-b">
        <div class="card-body">
            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
                    <button type="button" class="btn btn-lg btn-success" data-toggle="modal" data-target="#modalRegisterBalanca">
                        <i class="fa fa-plus"></i> Nova Balança
                    </button>
                </div>
            </div>
            <br>

            <!-- Lista de Balanças -->
            <div class="table-responsive">
                <h4>Lista de Balanças</h4>
                <label>Total de registros: {{ count($balancas ?? []) }}</label>
                <!-- Legenda -->
                <div style="display: flex; flex-direction: column; align-items: flex-start; margin-right: 15px; font-family: Arial, sans-serif; font-size: 12px; color: #333;">
                    <!-- Status: Conectado e Funcionando -->
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <span class="status-led-legenda" style="display: inline-block; width: 15px; height: 15px; border-radius: 50%; background-color: green; margin-right: 8px; border: 1px solid #000;"></span>
                        <span>
                            <strong>Conectado:</strong> Balança <span style="color: green; font-weight: bold;">Online</span> e Funcionando!
                        </span>
                    </div>

                    <!-- Status: Conectado, mas sem transmissão -->
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <span class="status-led-legenda" style="display: inline-block; width: 15px; height: 15px; border-radius: 50%; background-color: orange; margin-right: 8px; border: 1px solid #000;"></span>
                        <span>
                            <strong>Conectado:</strong> Balança <span style="color: orange; font-weight: bold;">Online</span>, mas <span style="font-weight: bold;">Sem Transmissão</span>.
                        </span>
                    </div>

                    <!-- Status: Inativo -->
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <span class="status-led-legenda" style="display: inline-block; width: 15px; height: 15px; border-radius: 50%; background-color: white; margin-right: 8px; border: 1px solid #000;"></span>
                        <span>
                            <strong>Inativo:</strong> Balança <span style="color: gray; font-weight: bold;">Offline</span> ou <span style="font-weight: bold;">Porta Fechada</span>.
                        </span>
                    </div>

                    <!-- Status: Erro -->
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <span class="status-led-legenda" style="display: inline-block; width: 15px; height: 15px; border-radius: 50%; background-color: red; margin-right: 8px; border: 1px solid #000;"></span>
                        <span>
                            <strong>Erro:</strong> <span style="color: red; font-weight: bold;">Serviço Backend Offline</span>.
                        </span>
                    </div>
                </div>
                <div class="table-responsive mt-3 table-balancas-scroll" style=" overflow-y: auto; overflow-x: auto;">
                    <table class="table table-bordered table-hover table-balancas">
                        <thead class="thead-light" style="position: sticky; top: -1; z-index: 1;">
                        <tr class="datatable-row" style="left: 0px;">
                            <th style="width: 0%; white-space: nowrap;">ID</th>
                            <th style="width: 1%; white-space: nowrap;">Descrição</th>
                            <th style="width: 1%; white-space: nowrap;">Backend Server Address</th>
                            <th style="width: 0%; white-space: nowrap;">Online</th>
                            <th style="width: 1%; white-space: nowrap;">Tipo</th>
                            <th style="width: 0%; white-space: nowrap;">Status</th>
                            <th style="width: 0%; white-space: nowrap;">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($balancas as $balanca)
                            <tr class="datatable-row">
                                <td class="datatable-cell" style="white-space: nowrap; font-size: 12px;">{{ $balanca->id }}</td>
                                <td class="datatable-cell" style="white-space: nowrap; font-size: 12px;">{{ $balanca->descricao }}</td>
                                <td class="datatable-cell" style="white-space: nowrap; font-size: 12px;">{{ $balanca->backend_server_address }}</td>
                                <td class="datatable-cell text-center">
                                    <!-- LED principal e legenda -->
                                     <span
                                        id="status-led-{{ $balanca->id }}"
                                        class="status-led"
                                        data-backend="{{ $balanca->backend_server_address }}"
                                        data-equip="{{ $balanca->modelo }}"
                                        style="width: 25px; height: 25px;">
                                     </span>
                                </td>
                                <td class="datatable-cell" style="white-space: nowrap; font-size: 12px;">{{ $balanca->getTipoLabelAttribute() }}</td>
                                <td class="datatable-cell" style="white-space: nowrap; font-size: 12px;">
                                    <span class="badge {{ $balanca->ativo ? 'badge-success' : 'badge-danger' }}">
                                        {{ $balanca->ativo ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="datatable-cell" style="white-space: nowrap; font-size: 12px;">
                                    <!-- Botões de ação -->
                                    <button type="button" class="btn btn-warning btn-sm btn-custom" data-toggle="modal" data-target="#modalEditBalanca{{ $balanca->id }}">
                                        <i class="la la-edit"></i> Editar
                                    </button>
                                    @if(is_adm())
                                        <form action="{{ route('balancas.delete', $balanca->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm btn-custom" onclick="return confirm('Deseja excluir este registro? Esta ação não poderá ser desfeita.')">
                                                <i class="la la-trash"></i> Excluir
                                            </button>
                                        </form>

                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Nenhuma balança cadastrada.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nova Balança -->
    <div class="modal fade " id="modalRegisterBalanca" tabindex="-1" role="dialog" >
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Nova Balança</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('balancas.save') }}">
                        @csrf
                        <div class="row">
                            <!-- Campos do formulário -->
                            <div class="form-group col-lg-6">
                                <label>Descrição:</label>
                                <input type="text" name="descricao" class="form-control" required>
                            </div>
                            <div class="form-group col-lg-6">
                                <label>Backend: https://127.0.0.1:3333 </label>
                                <input type="text" name="backend_server_address" class="form-control" required>
                            </div>
                            <!-- Porta -->
                            <div class="form-group col-lg-6">
                                <label>Porta:</label>
                                <div class="input-group">
                                    <select id="port-list" name="port" class="form-control"></select>
                                    <div class="input-group-append">
                                        <button type="button" id="listarPortas" class="btn btn-primary" onclick="listarPortasNova()">Listar</button>
                                    </div>
                                </div>
                            </div>
                            <!-- Equipamento -->
                            <div class="form-group col-lg-6">
                                <label>Equipamento:</label>
                                <select id="equip-list" name="modelo" class="form-control"></select>
                            </div>
                            <div class="form-group col-lg-6">
                                <label>Serial Number:</label>
                                <input type="text" name="serie_number" class="form-control" required>
                            </div>
                            <div class="form-group col-lg-6">
                                <label>Status:</label>
                                <select name="ativo" class="form-control" required>
                                    <option value="1">Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-6">
                                <label>Tipo da Balança:</label>
                                <select name="tipo" class="form-control" required>
                                    @foreach(App\Models\BalancaConfig::tipos() as $tipo => $descricao)
                                        <option value="{{ $tipo }}">{{ $descricao }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-lg-4">
                                <label>Integrador:</label>
                                <select name="integrador" class="form-control">
                                    <option value="local">Local</option>
                                    <option value="adp">A.D.P (ALL-DRIVER-PLATFORM)</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-4">
                                <label>UUID da balança no ADP:</label>
                                <input type="text" name="adp_scale_uuid" class="form-control" placeholder="uuid da /api/scales">
                            </div>
                            <div class="form-group col-lg-4">
                                <label>Usa câmeras?</label>
                                <select name="usa_cameras" class="form-control">
                                    <option value="0">Não</option>
                                    <option value="1">Sim</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-4">
                                <label>Qtd. câmeras:</label>
                                <input type="number" min="0" max="8" name="quantidade_cameras" value="0" class="form-control">
                            </div>
                            <div class="form-group col-lg-8">
                                <label>UUIDs das câmeras ADP (separados por vírgula):</label>
                                <input type="text" name="adp_camera_uuids[]" class="form-control" placeholder="uuid-camera-1,uuid-camera-2">
                            </div>
                            <div class="form-group col-lg-12">
                                <label>Observações:</label>
                                <textarea name="observacoes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Salvar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Balança -->
    @foreach($balancas as $balanca)
        <div class="modal fade" id="modalEditBalanca{{ $balanca->id }}" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Editar Balança #{{ $balanca->id }}</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('balancas.update', $balanca->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <!-- Campos do formulário -->
                                <div class="form-group col-lg-6">
                                    <label>Descrição:</label>
                                    <input type="text" name="descricao" value="{{ $balanca->descricao }}" class="form-control" required>
                                </div>
                                <div class="form-group col-lg-6">
                                    <label>Backend Server Address: https://127.0.0.1:3333</label>
                                    <input type="text" id="backendServerAddressEdit_{{ $balanca->id }}" name="backend_server_address" value="{{ $balanca->backend_server_address }}" class="form-control" required>
                                </div>
                                <!-- Porta -->
                                <div class="form-group col-lg-6">
                                    <label>Porta:</label>
                                    <div class="input-group">
                                        <select id="port-list-edit-{{ $balanca->id }}" name="port" class="form-control">
                                            <option value="{{ $balanca->port }}">{{ $balanca->port }}</option>
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" id="listarPortasEdit_{{ $balanca->id }}" class="btn btn-primary"
                                                    onclick="listarPortas({{ $balanca->id }})">Listar</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Equipamento -->
                                <div class="form-group col-lg-6">
                                    <label>Equipamento:</label>
                                    <select id="equip-list-edit-{{ $balanca->id }}" name="modelo" class="form-control">
                                        <option value="{{ $balanca->modelo }}">{{ $balanca->modelo }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-lg-6">
                                    <label>Serial Number:</label>
                                    <input type="text" name="serie_number" value="{{ $balanca->serie_number }}" class="form-control" required>
                                </div>
                                <div class="form-group col-lg-6">
                                    <label>Status:</label>
                                    <select name="ativo" class="form-control" required>
                                        <option value="1" {{ $balanca->ativo ? 'selected' : '' }}>Ativo</option>
                                        <option value="0" {{ !$balanca->ativo ? 'selected' : '' }}>Inativo</option>
                                    </select>
                                </div>
                                <div class="form-group col-lg-6">
                                    <label>Tipo da Balança:</label>
                                    <select name="tipo" class="form-control" required>
                                        @foreach(App\Models\BalancaConfig::tipos() as $tipo => $descricao)
                                            <option value="{{ $tipo }}" {{ $balanca->tipo == $tipo ? 'selected' : '' }}>
                                                {{ $descricao }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-lg-4">
                                    <label>Integrador:</label>
                                    <select name="integrador" class="form-control">
                                        <option value="local" {{ ($balanca->integrador ?? 'local') === 'local' ? 'selected' : '' }}>Local</option>
                                        <option value="adp" {{ ($balanca->integrador ?? '') === 'adp' ? 'selected' : '' }}>A.D.P (ALL-DRIVER-PLATFORM)</option>
                                    </select>
                                </div>
                                <div class="form-group col-lg-4">
                                    <label>UUID da balança no ADP:</label>
                                    <input type="text" name="adp_scale_uuid" value="{{ $balanca->adp_scale_uuid ?? '' }}" class="form-control">
                                </div>
                                <div class="form-group col-lg-4">
                                    <label>Usa câmeras?</label>
                                    <select name="usa_cameras" class="form-control">
                                        <option value="0" {{ !($balanca->usa_cameras ?? false) ? 'selected' : '' }}>Não</option>
                                        <option value="1" {{ ($balanca->usa_cameras ?? false) ? 'selected' : '' }}>Sim</option>
                                    </select>
                                </div>
                                <div class="form-group col-lg-4">
                                    <label>Qtd. câmeras:</label>
                                    <input type="number" min="0" max="8" name="quantidade_cameras" value="{{ $balanca->quantidade_cameras ?? 0 }}" class="form-control">
                                </div>
                                <div class="form-group col-lg-8">
                                    <label>UUIDs das câmeras ADP (separados por vírgula):</label>
                                    <input type="text" name="adp_camera_uuids[]" value="{{ is_array($balanca->adp_camera_uuids ?? null) ? implode(',', $balanca->adp_camera_uuids) : '' }}" class="form-control">
                                </div>

                                <div class="form-group col-lg-12">
                                    <label>Observações:</label>
                                    <textarea name="observacoes" class="form-control" rows="3">{{ $balanca->observacoes }}</textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning">Atualizar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@endsection

@section('javascript')
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/balancaMain.js') }}"></script>

    <script>
        // Recupera o backendURL configurado no formulário
        const backendURL2 = "{{ $balanca->backend_server_address ?? '' }}";

        /**
         * Função para listar portas e equipamentos no modal de edição
         */
        async function listarPortas(id) {
            try {
                const backend = document.getElementById(`backendServerAddressEdit_${id}`).value || backendURL2;

                if (!backend) {
                    alert('Endereço do servidor backend não configurado!');
                    return;
                }

                // Consulta ao backend
                const response = await axios.get(`${backend}/api/configinfo`);
                const ports = response.data.portsAvailable || [];
                const equips = response.data.suportedEquipaments || [];

                // Preencher portas
                const portList = document.getElementById(`port-list-edit-${id}`);
                portList.innerHTML = "";
                ports.forEach(port => {
                    let option = document.createElement("option");
                    option.value = port;
                    option.text = port;
                    portList.appendChild(option);
                });

                // Preencher equipamentos
                const equipList = document.getElementById(`equip-list-edit-${id}`);
                equipList.innerHTML = "";
                equips.forEach(equip => {
                    let option = document.createElement("option");
                    option.value = equip;
                    option.text = equip;
                    equipList.appendChild(option);
                });

                alert('Portas e equipamentos listados com sucesso!');
            } catch (error) {
                console.error('Erro ao listar portas:', error);
                alert('Erro ao listar portas e equipamentos. Verifique o backend.');
            }
        }

        /**
         * Função para listar portas e equipamentos no modal de inclusão
         */
        async function listarPortasNova() {
            try {
                const backend = document.querySelector('[name="backend_server_address"]').value || backendURL2;

                if (!backend) {
                    alert('Endereço do servidor backend não configurado!');
                    return;
                }

                // Consulta ao backend
                const response = await axios.get(`${backend}/api/configinfo`);
                const ports = response.data.portsAvailable || [];
                const equips = response.data.suportedEquipaments || [];

                // Preencher portas
                const portList = document.getElementById('port-list');
                portList.innerHTML = "";
                ports.forEach(port => {
                    let option = document.createElement("option");
                    option.value = port;
                    option.text = port;
                    portList.appendChild(option);
                });

                // Preencher equipamentos
                const equipList = document.getElementById('equip-list');
                equipList.innerHTML = "";
                equips.forEach(equip => {
                    let option = document.createElement("option");
                    option.value = equip;
                    option.text = equip;
                    equipList.appendChild(option);
                });

                alert('Portas e equipamentos listados com sucesso!');
            } catch (error) {
                console.error('Erro ao listar portas:', error);
                alert('Erro ao listar portas e equipamentos. Verifique o backend.');
            }
        }

        /**
         * Atualiza os campos 'port' e 'modelo' ao selecionar os valores
         */
        function atualizarCamposNovo() {
            const portaSelecionada = document.getElementById('port-list').value;
            const equipamentoSelecionado = document.getElementById('equip-list').value;

            document.querySelector('[name="port"]').value = portaSelecionada;
            document.querySelector('[name="modelo"]').value = equipamentoSelecionado;
        }

        function atualizarCamposEditar(id) {
            const portaSelecionada = document.getElementById(`port-list-edit-${id}`).value;
            const equipamentoSelecionado = document.getElementById(`equip-list-edit-${id}`).value;

            document.querySelector(`[name="port"]`).value = portaSelecionada;
            document.querySelector(`[name="modelo"]`).value = equipamentoSelecionado;
        }
    </script>

    <script>
        window.balancas = @json($balancas);
    </script>

    <!-- Script para Verificação Automática -->
    <script>
        // Verificar todas as balanças ao carregar o formulário
        document.addEventListener('DOMContentLoaded', () => {
            // Garante que os dados das balanças foram carregados
            if (!window.balancas || !window.balancas.data) {
                console.error("Nenhuma balança carregada!");
                return;
            }

            // Iterar sobre todas as balanças carregadas
            window.balancas.data.forEach(balanca => {
                verificarStatusBalanca(
                    balanca.id,
                    balanca.backend_server_address,
                    balanca.modelo
                ).then(() => {
                    console.log(`Verificação automática concluída para balança ID ${balanca.id}`);
                }).catch(err => {
                    console.error(`Erro na verificação automática da balança ID ${balanca.id}:`, err);
                });
            });
        });

        // Atualizar automaticamente a cada 10 segundos
        setInterval(() => {
            window.balancas.data.forEach(balanca => {
                verificarStatusBalanca(
                    balanca.id,
                    balanca.backend_server_address,
                    balanca.modelo
                ).catch(err => console.error(`Erro ao atualizar balança ID ${balanca.id}:`, err));
            });
        }, 10000); // 10 segundos
    </script>

@endsection
