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
                    <a href="{{ route('adp.cameras.list') }}" class="btn btn-lg btn-info ml-2">
                        <i class="fa fa-camera"></i> Câmeras ADP
                    </a>
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
                            <th style="width: 1%; white-space: nowrap;">Integração ADP</th>
                            <th style="width: 1%; white-space: nowrap;">Base URL ADP</th>
                            <th style="width: 1%; white-space: nowrap;">Câmeras</th>
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
                                <td class="datatable-cell" style="white-space: nowrap; font-size: 12px;">{{ $balanca->integrador_config_id ? 'Config #' . $balanca->integrador_config_id : 'Config pendente' }}<br><small>{{ $balanca->adp_scale_uuid }}</small></td>
                                <td class="datatable-cell" style="white-space: nowrap; font-size: 12px;">{{ $balanca->backend_server_address ?: '-' }}</td>
                                <td class="datatable-cell" style="white-space: nowrap; font-size: 12px;">
                                    <span class="badge {{ ($balanca->quantidade_cameras ?? 0) > 0 ? 'badge-info' : 'badge-secondary' }}">{{ (int) ($balanca->quantidade_cameras ?? 0) }} câmera(s)</span><br>
                                    <small data-adp-camera-health-summary="{{ $balanca->id }}">Aguardando</small>
                                </td>
                                <td class="datatable-cell text-center">
                                    <!-- LED principal e legenda -->
                                     <span
                                        id="status-led-{{ $balanca->id }}"
                                        class="status-led"
                                        data-backend="{{ $balanca->backend_server_address }}"
                                        data-equip="{{ $balanca->modelo }}"
                                        data-integrador="{{ $balanca->integrador ?? 'legacy' }}"
                                        data-integrador-config-id="{{ $balanca->integrador_config_id ?? '' }}"
                                        data-adp-scale-uuid="{{ $balanca->adp_scale_uuid ?? '' }}"
                                        data-adp-camera-uuids='{{ $balanca->adp_camera_uuids ?? "[]" }}'
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
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Nova Balança</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('balancas.save') }}">
                        @csrf
                        <input type="hidden" name="integrador" value="adp">
                        <div class="alert alert-info py-2 mb-2">Integração ADP ativa para leitura e monitoramento da balança.</div>
                        <div class="row">
                            <!-- Campos do formulário -->
                            <div class="form-group col-lg-6">
                                <label>Descrição:</label>
                                <input type="text" name="descricao" class="form-control" required>
                            </div>
                            <div class="col-12 js-adp-section" data-target-prefix="new">
                                @include('balanca_config.partials.adp-form', ['balanca' => null])
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
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Editar Balança #{{ $balanca->id }}</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('balancas.update', $balanca->id) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="integrador" value="adp">
                            <div class="alert alert-info py-2 mb-2">Integração ADP ativa para leitura e monitoramento da balança.</div>
                            <div class="row">
                                <!-- Campos do formulário -->
                                <div class="form-group col-lg-6">
                                    <label>Descrição:</label>
                                    <input type="text" name="descricao" value="{{ $balanca->descricao }}" class="form-control" required>
                                </div>
                                <div class="col-12 js-adp-section" data-target-prefix="edit-{{ $balanca->id }}">
                                    @include('balanca_config.partials.adp-form', ['balanca' => $balanca])
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
    <script>
        window.balancas = @json($balancas);
    </script>
    <script src="{{ asset('js/adp-runtime-client.js') }}"></script>
    <script src="{{ asset('js/balancaMain.js') }}"></script>

@endsection
