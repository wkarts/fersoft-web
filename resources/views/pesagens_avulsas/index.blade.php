@extends('default.layout')
@section('content')
    <style>
        .pdv-container { display: flex; flex-wrap: wrap; margin: -10px; }
        .pdv-left { flex: 0 0 46%; max-width: 46%; padding: 10px; }
        .pdv-right { flex: 0 0 54%; max-width: 54%; padding: 10px; }
        .pdv-panel { background: #fff; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); padding: 15px; }
        .ticket-list { max-height: 360px; overflow-y: auto; }
        .itens-list { max-height: 240px; overflow-y: auto; }
        .peso-display { font-size: 2.5rem; font-family: monospace; color: #1BC5BD; line-height: 1; margin-bottom: 0; }

        .table-pesagens th { font-size: 11px; text-transform: uppercase; vertical-align: middle !important; }
        .table-pesagens td { font-size: 12.5px; vertical-align: middle !important; white-space: nowrap; font-weight: bold; }
        .tr-selecionada { background-color: #e8f4ff !important; outline: 2px solid #3699ff; }
        .bg-andamento { background-color: #ffeb3b !important; color: #000; }
        .bg-finalizado { background-color: #28a745 !important; color: #fff; }
        .bg-cancelado { background-color: #dc3545 !important; color: #fff; }
        .btn-action-icon { padding: 4px 8px; font-size: 12px; margin: 0 1px; }
        .input-travado { background-color: #e9ecef !important; cursor: not-allowed; }

        /* Lista suspensa de busca de cliente por nome */
        .cliente-autocomplete-wrapper { position: relative; }
        .cliente-sugestoes-box {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: #fff;
            border: 1px solid #3699ff;
            border-radius: 0 0 6px 6px;
            max-height: 230px;
            overflow-y: auto;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            display: none;
        }
        .cliente-sugestao-item {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f1f1;
            cursor: pointer;
            font-size: 12.5px;
        }
        .cliente-sugestao-item:hover { background-color: #e8f4ff; }

        @media(max-width: 1200px) { .pdv-left, .pdv-right { flex: 0 0 100%; max-width: 100%; } }
    </style>

    <div class="pdv-container">
        <!-- ESQUERDA: FORMULÁRIO DE OPERAÇÃO -->
        <div class="pdv-left">
            <div class="pdv-panel border-top border-primary border-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-primary font-weight-bold m-0"><i class="fa fa-balance-scale"></i> Serviço de Pesagem</h4>
                    <span id="badgeModoOperacao" class="badge badge-primary p-2" style="font-size: 12px;">MODO: 1ª PESAGEM (ENTRADA)</span>
                </div>

                <form id="formPesagemAvulsa" autocomplete="off">
                    @csrf
                    <input type="hidden" name="id" id="pesagem_id">
                    <input type="hidden" name="item_id" id="item_id">
                    <input type="hidden" name="cliente_id" id="cliente_id">
                    <input type="hidden" name="status" id="status_pesagem" value="em andamento">
                    <input type="hidden" name="acao" id="acao_form" value="salvar_entrada">
                    <input type="hidden" name="balanca_evidence_json" id="balanca_evidence_json">
                    <input type="hidden" name="camera_snapshots_json" id="camera_snapshots_json">

                    <!-- Dados do Cliente e Caminhão -->
                    <div class="row bg-light p-3 rounded mb-3 mx-0 border">
                        <div class="col-md-5 form-group mb-2">
                            <label class="font-weight-bold">CNPJ/CPF <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="cnpj_busca" id="cnpj_busca" class="form-control" placeholder="Digite o CNPJ ou CPF" required>
                                <div class="input-group-append">
                                    <button type="button" id="btnBuscarCnpj" class="btn btn-info" title="Buscar na Receita / Cadastrar"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7 form-group mb-2 cliente-autocomplete-wrapper">
                            <label class="font-weight-bold">Nome / Razão Social <small class="text-primary">(Digite p/ buscar cadastrado)</small></label>
                            <div class="input-group">
                                <input type="text" name="cliente_nome" id="cliente_nome" class="form-control text-uppercase" placeholder="Digite o nome para buscar ou cadastrar">
                                <div class="input-group-append">
                                    <button type="button" id="btnPesquisarClienteNome" class="btn btn-outline-primary" title="Pesquisar cliente cadastrado"><i class="fa fa-users"></i></button>
                                </div>
                            </div>
                            <div id="listaSugestoesClientes" class="cliente-sugestoes-box"></div>
                        </div>

                        <div class="col-md-4 form-group mb-0">
                            <label class="font-weight-bold">Placa Caminhão <span class="text-danger">*</span></label>
                            <input type="text" name="placa_veiculo" id="placa_veiculo" class="form-control text-uppercase" maxlength="8" required>
                        </div>
                        <div class="col-md-4 form-group mb-0">
                            <label class="font-weight-bold">Placa Carreta</label>
                            <input type="text" name="placa_carreta" id="placa_carreta" class="form-control text-uppercase" maxlength="8">
                        </div>
                        <div class="col-md-4 form-group mb-0">
                            <label class="font-weight-bold">Motorista <span class="text-danger">*</span></label>
                            <input type="text" name="motorista_nome" id="motorista_nome" class="form-control text-uppercase" required>
                        </div>
                    </div>

                    <!-- CHAVE SELETORA: SEM ITEM vs COM ITENS -->
                    <div class="d-flex justify-content-between align-items-center bg-primary-o-15 p-2 px-3 rounded mb-3 border border-primary">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="check_com_itens" name="com_itens" value="1">
                            <label class="custom-control-label font-weight-bold text-primary" for="check_com_itens" style="cursor:pointer; font-size:13.5px;">
                                <i class="fa fa-boxes mr-1"></i> Informar Itens / Produtos nesta Pesagem
                            </label>
                        </div>
                        <small class="text-muted font-weight-bold" id="hintModoItens">Desmarcado: Só Peso Cheio e Vazio</small>
                    </div>

                    <!-- SELEÇÃO DO PRODUTO (Exibido apenas quando "Informar Itens" está marcado) -->
                    <div id="boxSelecaoProduto" class="bg-light p-3 rounded mb-3 border border-info" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="font-weight-bold text-info mb-0"><i class="fa fa-cube"></i> Produto / Item sendo pesado agora:</label>
                            <span id="badgeEtapaItem" class="badge badge-info">NOVO ITEM (1º PESO: ENTRADA)</span>
                        </div>
                        <select name="produto_id" id="produto_id" class="form-control select2-produto" style="width: 100%;">
                            <option value="">-- Selecione um Produto do Cadastro --</option>
                            @foreach($produtos as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->referencia ? $prod->referencia . ' | ' : '' }}{{ $prod->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Display da Balança com todos os atributos ADP originais -->
                    <div class="row bg-dark p-3 rounded mb-3 mx-0 text-white align-items-center">
                        <div class="col-md-7">
                            <label class="small text-uppercase text-muted mb-1">
                                Balança Rodoviária (ADP): <strong id="pesoEstabilidade" class="text-warning ml-2"></strong>
                            </label>
                            <div class="input-group">
                                <select id="balanca-select" class="form-control form-control-sm text-dark">
                                    <option value="">Selecione</option>
                                    @foreach($balancas as $balanca)
                                        <option value="{{ $balanca->id }}"
                                                {{ (int) ($balancaPadraoUsuarioId ?? 0) === (int) $balanca->id ? 'selected' : '' }}
                                                data-backend="{{ $balanca->backend_server_address }}"
                                                data-modelo="{{ $balanca->modelo }}"
                                                data-port="{{ $balanca->port ?? $balanca->porta_serial }}"
                                                data-integrador="{{ $balanca->integrador ?? 'adp' }}"
                                                data-integrador-config-id="{{ $balanca->integrador_config_id ?? '' }}"
                                                data-adp-scale-uuid="{{ $balanca->adp_scale_uuid ?? '' }}"
                                                data-adp-camera-uuids='@json(json_decode((string) ($balanca->adp_camera_uuids ?? "[]"), true) ?: [])'>
                                            {{ $balanca->descricao }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button id="connect" type="button" class="btn btn-success btn-sm" title="Conectar Balança"><i class="fa fa-link"></i></button>
                                    <button id="disconnect" type="button" class="btn btn-danger btn-sm" disabled title="Desconectar"><i class="fa fa-unlink"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 text-right">
                            <h2 class="peso-display" id="pesoAtual">0.00</h2>
                            <small class="text-muted">kg na balança</small>
                        </div>
                    </div>

                    <!-- Campos de Peso (Entrada / Saída / Saldo) -->
                    <div class="row align-items-end">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold text-primary" id="lblPesoEntrada">1º Peso: Entrada (kg)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="peso_entrada" id="peso_entrada" class="form-control form-control-lg font-weight-bold text-primary" placeholder="0.00" {{ ($configNota->bloquear_pesagem_manual_balanca ?? 0) ? 'readonly' : '' }}>
                                <div class="input-group-append">
                                    <button type="button" id="btnCapturarEntrada" class="btn btn-primary btn-capturar" data-target="peso_entrada" disabled title="Capturar Entrada da Balança"><i class="fa fa-crosshairs"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold text-danger" id="lblPesoSaida">2º Peso: Saída (kg)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="peso_saida" id="peso_saida" class="form-control form-control-lg font-weight-bold text-danger input-travado" placeholder="Aguardando..." readonly>
                                <div class="input-group-append">
                                    <button type="button" id="btnCapturarSaida" class="btn btn-danger btn-capturar" data-target="peso_saida" disabled title="Capturar Saída da Balança"><i class="fa fa-crosshairs"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold text-dark">Saldo / Líquido (kg)</label>
                            <input type="text" id="saldo_preview" class="form-control form-control-lg font-weight-bold text-center bg-light" value="0" readonly>
                        </div>
                    </div>

                    <!-- Valor do Serviço e Observação -->
                    <div class="row bg-warning-o-20 p-2 rounded border mb-3 mx-0">
                        <div class="col-md-5 form-group mb-0">
                            <label class="font-weight-bold mb-1"><i class="fa fa-dollar-sign"></i> Valor do Serviço (R$)</label>
                            <input type="number" step="0.01" name="valor_servico" id="valor_servico" class="form-control text-success font-weight-bold" placeholder="0.00 (0 = não imprime)">
                        </div>
                        <div class="col-md-7 form-group mb-0">
                            <label class="font-weight-bold mb-1"><i class="fa fa-comment-alt"></i> Observação (Sai na Impressão)</label>
                            <input type="text" name="observacao" id="observacao" class="form-control" placeholder="Digite uma observação opcional p/ o ticket...">
                        </div>
                    </div>

                    <!-- BOTÕES INTELIGENTES CONFORME O MODO (SEM ITEM vs COM ITENS) -->
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap" style="gap: 6px;">
                        <button type="button" class="btn btn-secondary font-weight-bold" id="btnNovaLimpar">
                            <i class="fa fa-undo"></i> Nova / Limpar
                        </button>

                        <!-- Botões do Modo SEM ITEM -->
                        <div id="botoesSemItem">
                        <span id="grupoBotoesEntrada">
                            <button type="button" class="btn btn-warning font-weight-bold mr-1" id="btnSalvarEntrada">
                                <i class="fa fa-sign-in-alt"></i> Registrar Entrada (Aguardar Saída)
                            </button>
                            <button type="button" class="btn btn-info font-weight-bold" id="btnFinalizarDireto">
                                <i class="fa fa-check"></i> Finalizar (Só 1 Peso)
                            </button>
                        </span>
                            <span id="grupoBotoesSaida" style="display: none;">
                            <button type="button" class="btn btn-success btn-lg font-weight-bold" id="btnFinalizarSaida">
                                <i class="fa fa-check-double"></i> Registrar Saída e Finalizar
                            </button>
                        </span>
                        </div>

                        <!-- Botões do Modo COM ITENS -->
                        <div id="botoesComItens" style="display: none;">
                            <button type="button" class="btn btn-info font-weight-bold mr-1" id="btnSalvarItem">
                                <i class="fa fa-plus-circle"></i> <span id="txtBtnSalvarItem">Registrar Entrada do Item</span>
                            </button>
                            <button type="button" class="btn btn-success font-weight-bold" id="btnFinalizarComItens">
                                <i class="fa fa-check-double"></i> Finalizar Pesagem
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- DIREITA: TABELA DE PESAGENS RECENTES + PAINEL DE ITENS EMBAIXO -->
        <div class="pdv-right">
            <!-- Painel Superior: Pesagens Recentes -->
            <div class="pdv-panel border-top border-dark border-3 bg-light mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 8px;">
                    <h4 class="text-dark font-weight-bold m-0"><i class="fa fa-list"></i> Pesagens Recentes</h4>

                    <div class="d-flex align-items-center" style="gap: 12px;">
                        <div class="custom-control custom-checkbox bg-white px-2 py-1 rounded border">
                            <input type="checkbox" class="custom-control-input" id="check_imprimir_com_itens" checked>
                            <label class="custom-control-label font-weight-bold text-dark small" for="check_imprimir_com_itens" style="cursor:pointer;">
                                Imprimir c/ Itens no 80mm
                            </label>
                        </div>

                        <button type="button" class="btn btn-sm btn-dark font-weight-bold" data-toggle="modal" data-target="#modalHistorico">
                            <i class="fa fa-history"></i> Consultar Antigas
                        </button>
                    </div>
                </div>

                <div class="ticket-list pr-1">
                    <table class="table table-bordered table-hover table-pesagens text-center bg-white mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th class="text-left">PLACA - MOTORISTA</th>
                            <th>MODO</th>
                            <th>ENTRADA</th>
                            <th>SAÍDA</th>
                            <th>SALDO</th>
                            <th>STATUS</th>
                            <th>AÇÃO</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($pesagensRecentes as $pesagem)
                            <tr class="tr-pesagem-row" data-id="{{ $pesagem->id }}" data-pesagem="{{ json_encode($pesagem) }}" style="cursor: pointer;" title="Clique para carregar ou ver os itens abaixo">
                                <td class="text-left">
                                    {{ $pesagem->placa_veiculo }} - {{ Str::limit($pesagem->motorista_nome, 12) }}
                                </td>
                                <td>
                                    @if($pesagem->com_itens)
                                        <span class="badge badge-info">{{ $pesagem->itens->count() }} {{ $pesagem->itens->count() == 1 ? 'ITEM' : 'ITENS' }}</span>
                                    @else
                                        <span class="badge badge-secondary">AVULSA</span>
                                    @endif
                                </td>
                                <td>{{ $pesagem->peso_bruto > 0 ? number_format($pesagem->peso_bruto, 0, ',', '.') : '' }}</td>
                                <td>{{ $pesagem->tara > 0 ? number_format($pesagem->tara, 0, ',', '.') : '' }}</td>
                                <td class="text-primary">{{ $pesagem->peso_liquido > 0 ? number_format($pesagem->peso_liquido, 0, ',', '.') : '' }}</td>

                                @if($pesagem->status == 'em andamento')
                                    <td class="bg-andamento">ANDAMENTO</td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-action-icon btn-editar"
                                                data-pesagem="{{ json_encode($pesagem) }}"
                                                title="Carregar Pesagem / Informar Saída ou Próximo Item">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-action-icon btn-cancelar" data-id="{{ $pesagem->id }}" title="Cancelar Pesagem">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </td>
                                @elseif($pesagem->status == 'concluído')
                                    <td class="bg-finalizado">FINALIZADO</td>
                                    <td>
                                        <button type="button" class="btn btn-dark btn-action-icon btn-imprimir-80mm" data-id="{{ $pesagem->id }}" title="Imprimir Ticket 80mm">
                                            <i class="fa fa-print"></i>
                                        </button>
                                        <button type="button" class="btn btn-success btn-action-icon btn-whatsapp" data-id="{{ $pesagem->id }}" title="Enviar via WhatsApp">
                                            <i class="fa fa-whatsapp"></i>
                                        </button>
                                    </td>
                                @else
                                    <td class="bg-cancelado" colspan="2">CANCELADO</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">Nenhuma pesagem registrada hoje.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PAINEL INFERIOR: ITENS DA PESAGEM SELECIONADA -->
            <div class="pdv-panel border-top border-info border-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-info font-weight-bold m-0">
                        <i class="fa fa-boxes"></i> Itens da Pesagem <span id="lblItensPesagemId" class="text-dark"></span>
                    </h5>
                    <button type="button" class="btn btn-xs btn-light-info font-weight-bold" id="btnPrepararNovoItem" style="display: none;">
                        <i class="fa fa-plus"></i> Incluir Próximo Item neste Caminhão
                    </button>
                </div>

                <div class="itens-list">
                    <table class="table table-bordered table-sm table-pesagens text-center mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th class="text-left">PRODUTO / ITEM</th>
                            <th>ENTRADA (KG)</th>
                            <th>SAÍDA (KG)</th>
                            <th>LÍQUIDO (KG)</th>
                            <th>STATUS</th>
                            <th>AÇÃO</th>
                        </tr>
                        </thead>
                        <tbody id="tabelaItensPesagemBody">
                        <tr>
                            <td colspan="6" class="text-muted">Clique em uma pesagem na lista acima para visualizar ou pesar seus itens.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CONSULTAR ANTIGAS -->
    <div class="modal fade" id="modalHistorico" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fa fa-history"></i> Histórico de Pesagens</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formFiltroHistorico" class="row mb-4 align-items-end">
                        <div class="col-md-3">
                            <label>Data Início</label>
                            <input type="date" name="data_inicio" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label>Data Fim</label>
                            <input type="date" name="data_fim" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label>Placa (Opcional)</label>
                            <input type="text" name="placa" class="form-control text-uppercase">
                        </div>
                        <div class="col-md-3">
                            <button type="button" id="btnBuscarHistorico" class="btn btn-info w-100 font-weight-bold"><i class="fa fa-search"></i> Pesquisar</button>
                        </div>
                    </form>

                    <table class="table table-bordered table-striped text-center table-pesagens">
                        <thead class="thead-dark">
                        <tr>
                            <th>DATA</th>
                            <th>CLIENTE</th>
                            <th>PLACA - MOTORISTA</th>
                            <th>ENTRADA</th>
                            <th>SAÍDA</th>
                            <th>SALDO</th>
                            <th>STATUS</th>
                            <th>AÇÃO</th>
                        </tr>
                        </thead>
                        <tbody id="tabelaHistoricoBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/adp-runtime-client.js') }}"></script>
    <script>
        const BLOQUEAR_MANUAL = {{ (int) ($configNota->bloquear_pesagem_manual_balanca ?? 0) }};
        const BALANCA_PADRAO_USUARIO_ID = {{ (int) ($balancaPadraoUsuarioId ?? 0) }};
        const AUTO_CONNECT_BALANCA_PADRAO_USUARIO = {{ (int) ($configNota->conectar_automaticamente_balanca_padrao_usuario ?? 0) }};
        const AUTO_CONNECT_BALANCA_AO_SELECIONAR = {{ (int) ($configNota->conectar_automaticamente_balanca_ao_selecionar ?? 0) }};

        let autoState = {
            conectado: false,
            balancaConectada: null,
            intervaloLeitura: null
        };

        let pesagemSelecionadaAtual = null;
        let timerBuscaCliente = null;

        $(document).ready(function() {
            if ($.fn.select2) {
                $('#produto_id').select2({ placeholder: '-- Selecione um Produto do Cadastro --', width: '100%' });
            }

            if (BALANCA_PADRAO_USUARIO_ID > 0) {
                $('#balanca-select').val(String(BALANCA_PADRAO_USUARIO_ID));
                if (AUTO_CONNECT_BALANCA_PADRAO_USUARIO) {
                    setTimeout(() => $('#connect').trigger('click'), 100);
                }
            }
        });

        // =========================================================================
        // BUSCA DE CLIENTE JÁ CADASTRADO (POR NOME OU BOTÃO)
        // =========================================================================
        function pesquisarClientesCadastrados(termo) {
            $.get('/pesagens/search/cliente', { term: termo || '' })
                .done(function(res) {
                    const lista = res.results || [];
                    const $box = $('#listaSugestoesClientes');
                    $box.empty();

                    if (lista.length === 0) {
                        $box.html('<div class="p-2 text-muted small">Nenhum cliente cadastrado encontrado com esse nome. Digite o CNPJ ao lado para cadastrar automático.</div>').show();
                        return;
                    }

                    lista.forEach(cli => {
                        $box.append(`
                        <div class="cliente-sugestao-item"
                             data-id="${cli.id}"
                             data-cnpj="${cli.cpf_cnpj !== 'N/A' ? cli.cpf_cnpj : ''}"
                             data-razao="${cli.razao_social}">
                            <div class="font-weight-bold text-primary">${cli.razao_social}</div>
                            <small class="text-muted">CNPJ/CPF: ${cli.cpf_cnpj} | Cidade: ${cli.cidade}</small>
                        </div>
                    `);
                    });
                    $box.show();
                });
        }

        $('#cliente_nome').on('input', function() {
            if ($(this).prop('readonly')) return;
            const termo = $(this).val().trim();
            clearTimeout(timerBuscaCliente);

            if (termo.length < 2) {
                $('#listaSugestoesClientes').hide();
                return;
            }

            timerBuscaCliente = setTimeout(() => {
                pesquisarClientesCadastrados(termo);
            }, 280);
        });

        $('#btnPesquisarClienteNome').click(function() {
            if ($('#cliente_nome').prop('readonly')) return;
            const termo = $('#cliente_nome').val().trim();
            pesquisarClientesCadastrados(termo);
        });

        $(document).on('click', '.cliente-sugestao-item', function() {
            const id    = $(this).data('id');
            const cnpj  = $(this).data('cnpj');
            const razao = $(this).data('razao');

            $('#cliente_id').val(id);
            $('#cnpj_busca').val(cnpj);
            $('#cliente_nome').val(razao);
            $('#listaSugestoesClientes').hide();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.cliente-autocomplete-wrapper').length) {
                $('#listaSugestoesClientes').hide();
            }
        });

        function obterBalancaSelecionada() {
            const optionEl = $('#balanca-select option:selected')[0];
            if (!optionEl || !optionEl.value) return null;

            if (window.AdpRuntimeClient && typeof window.AdpRuntimeClient.buildBalancaFromSelectOption === 'function') {
                return window.AdpRuntimeClient.buildBalancaFromSelectOption(optionEl);
            }

            const $select = $(optionEl);
            return {
                id: $select.val(),
                backend: $select.data('backend'),
                modelo: $select.data('modelo'),
                porta: $select.data('port'),
                integrador: $select.data('integrador') || 'adp',
                integrador_config_id: $select.data('integrador-config-id') || '',
                adp_scale_uuid: $select.data('adp-scale-uuid') || '',
                adp_camera_uuids: $select.attr('data-adp-camera-uuids') || '[]',
                descricao: $select.text().trim()
            };
        }

        function atualizarSaldoPreview() {
            let pEntrada = parseFloat($('#peso_entrada').val()) || 0;
            let pSaida   = parseFloat($('#peso_saida').val()) || 0;
            let saldo    = pSaida > 0 ? Math.abs(pEntrada - pSaida) : pEntrada;
            $('#saldo_preview').val(saldo.toLocaleString('pt-BR'));
        }

        $('#peso_entrada, #peso_saida').on('input change', atualizarSaldoPreview);

        $('#check_com_itens').on('change', function() {
            const comItens = $(this).is(':checked');
            if (comItens) {
                $('#boxSelecaoProduto').slideDown(150);
                $('#botoesSemItem').hide();
                $('#botoesComItens').show();
                $('#hintModoItens').text('Marcado: Pesagem de 1 ou mais Produtos (Entrada e Saída por Item)');
            } else {
                $('#boxSelecaoProduto').slideUp(150);
                $('#botoesComItens').hide();
                $('#botoesSemItem').show();
                $('#hintModoItens').text('Desmarcado: Só Peso Cheio e Vazio');
            }
        });

        function carregarCabecalhoDaPesagemNoFormulario(pesagem) {
            const cnpj  = (pesagem.cliente && pesagem.cliente.cpf_cnpj) ? pesagem.cliente.cpf_cnpj : '';
            const razao = (pesagem.cliente && pesagem.cliente.razao_social) ? pesagem.cliente.razao_social : '';

            $('#pesagem_id').val(pesagem.id);
            $('#cliente_id').val(pesagem.cliente_id);

            $('#cnpj_busca').val(cnpj).prop('readonly', true).addClass('input-travado');
            $('#cliente_nome').val(razao).prop('readonly', true).addClass('input-travado');
            $('#btnBuscarCnpj, #btnPesquisarClienteNome').prop('disabled', true);
            $('#listaSugestoesClientes').hide();

            $('#placa_veiculo').val(pesagem.placa_veiculo).prop('readonly', true).addClass('input-travado');
            $('#placa_carreta').val(pesagem.placa_carreta || '').prop('readonly', true).addClass('input-travado');
            $('#motorista_nome').val(pesagem.motorista_nome).prop('readonly', true).addClass('input-travado');
            $('#valor_servico').val(parseFloat(pesagem.valor_servico) > 0 ? parseFloat(pesagem.valor_servico).toFixed(2) : '');
            $('#observacao').val(pesagem.observacao || '');
        }

        function renderizarPainelItens(pesagem) {
            pesagemSelecionadaAtual = pesagem;
            $('#lblItensPesagemId').text('#' + pesagem.id + ' (' + pesagem.placa_veiculo + ')');

            const $tbody = $('#tabelaItensPesagemBody');
            $tbody.empty();

            if (!pesagem.com_itens) {
                $('#btnPrepararNovoItem').hide();
                $tbody.html('<tr><td colspan="6" class="text-muted">Esta pesagem foi registrada no modo <strong>Sem Item (Avulsa)</strong>.</td></tr>');
                return;
            }

            const itens = pesagem.itens || [];
            const temItemEmAndamento = itens.some(i => i.status === 'em andamento');

            if (pesagem.status === 'em andamento' && !temItemEmAndamento) {
                $('#btnPrepararNovoItem').show();
            } else {
                $('#btnPrepararNovoItem').hide();
            }

            if (itens.length === 0) {
                $tbody.html('<tr><td colspan="6" class="text-muted">Nenhum item pesado ainda para este caminhão.</td></tr>');
                return;
            }

            itens.forEach(item => {
                const pEnt = parseFloat(item.peso_entrada || 0).toLocaleString('pt-BR');
                const pSai = parseFloat(item.peso_saida || 0) > 0 ? parseFloat(item.peso_saida).toLocaleString('pt-BR') : '---';
                const pLiq = parseFloat(item.peso_liquido || 0).toLocaleString('pt-BR');

                const badge = item.status === 'concluído'
                    ? '<span class="badge badge-success">CONCLUÍDO</span>'
                    : '<span class="badge badge-warning">AGUARDANDO SAÍDA</span>';

                let acoesHtml = '-';
                if (pesagem.status === 'em andamento') {
                    if (item.status === 'em andamento') {
                        acoesHtml = `
                        <button type="button" class="btn btn-primary btn-action-icon btn-pesar-saida-item" data-item='${JSON.stringify(item)}' title="Pesar Saída deste Item">
                            <i class="fa fa-edit"></i> Pesar Saída
                        </button>
                        <button type="button" class="btn btn-danger btn-action-icon btn-excluir-item" data-id="${item.id}" title="Excluir Item">
                            <i class="fa fa-trash"></i>
                        </button>`;
                    } else {
                        acoesHtml = `
                        <button type="button" class="btn btn-danger btn-action-icon btn-excluir-item" data-id="${item.id}" title="Excluir Item">
                            <i class="fa fa-trash"></i>
                        </button>`;
                    }
                }

                $tbody.append(`
                <tr>
                    <td class="text-left">${item.produto_nome}</td>
                    <td>${pEnt}</td>
                    <td>${pSai}</td>
                    <td class="text-primary">${pLiq}</td>
                    <td>${badge}</td>
                    <td>${acoesHtml}</td>
                </tr>
            `);
            });
        }

        function ativarModoEntrada() {
            $('#formPesagemAvulsa')[0].reset();
            $('#pesagem_id').val('');
            $('#item_id').val('');
            $('#cliente_id').val('');
            $('#observacao').val('');
            $('#produto_id').val('').trigger('change').prop('disabled', false);
            $('#listaSugestoesClientes').hide();

            if (autoState.balancaConectada) {
                $('#balanca-select').val(String(autoState.balancaConectada.id));
            }

            $('#cnpj_busca, #cliente_nome, #placa_veiculo, #placa_carreta, #motorista_nome')
                .prop('readonly', false)
                .removeClass('input-travado');
            $('#btnBuscarCnpj, #btnPesquisarClienteNome').prop('disabled', false);
            $('#check_com_itens').prop('disabled', false).prop('checked', false).trigger('change');

            $('#peso_entrada').prop('readonly', BLOQUEAR_MANUAL === 1).removeClass('input-travado');
            $('#peso_saida').val('').prop('readonly', true).addClass('input-travado').attr('placeholder', 'Aguardando...');

            $('#btnCapturarEntrada').prop('disabled', !autoState.conectado);
            $('#btnCapturarSaida').prop('disabled', true);

            $('#badgeModoOperacao').removeClass('badge-warning').addClass('badge-primary').text('MODO: 1ª PESAGEM (ENTRADA)');
            $('#badgeEtapaItem').removeClass('badge-warning').addClass('badge-info').text('NOVO ITEM (1º PESO: ENTRADA)');
            $('#txtBtnSalvarItem').text('Registrar Entrada do Item');
            $('#grupoBotoesEntrada').show();
            $('#grupoBotoesSaida').hide();
            atualizarSaldoPreview();
        }

        $('#btnNovaLimpar').click(ativarModoEntrada);

        function prepararSaidaDeItem(pesagem, item) {
            carregarCabecalhoDaPesagemNoFormulario(pesagem);
            $('#check_com_itens').prop('checked', true).trigger('change');

            $('#item_id').val(item.id);
            $('#produto_id').val(item.produto_id || '').trigger('change').prop('disabled', true);

            let pEnt = parseFloat(item.peso_entrada) || 0;
            $('#peso_entrada').val(pEnt.toFixed(2)).prop('readonly', true).addClass('input-travado');
            $('#peso_saida').val('').prop('readonly', BLOQUEAR_MANUAL === 1).removeClass('input-travado').attr('placeholder', '0.00').focus();

            $('#btnCapturarEntrada').prop('disabled', true);
            $('#btnCapturarSaida').prop('disabled', !autoState.conectado);

            $('#badgeModoOperacao').removeClass('badge-primary').addClass('badge-warning').text('SAÍDA DO ITEM: ' + item.produto_nome);
            $('#badgeEtapaItem').removeClass('badge-info').addClass('badge-warning').text('2º PESO DO ITEM: SAÍDA');
            $('#txtBtnSalvarItem').text('Registrar Saída do Item');
            atualizarSaldoPreview();
        }

        function prepararNovoItemNoCaminhao(pesagem) {
            carregarCabecalhoDaPesagemNoFormulario(pesagem);
            $('#check_com_itens').prop('checked', true).trigger('change');

            $('#item_id').val('');
            $('#produto_id').val('').trigger('change').prop('disabled', false);

            const itens = pesagem.itens || [];
            const ultimoConcluido = [...itens].reverse().find(i => parseFloat(i.peso_saida) > 0);
            const pesoSugerido = ultimoConcluido ? parseFloat(ultimoConcluido.peso_saida).toFixed(2) : '';

            $('#peso_entrada').val(pesoSugerido).prop('readonly', BLOQUEAR_MANUAL === 1).removeClass('input-travado');
            $('#peso_saida').val('').prop('readonly', true).addClass('input-travado').attr('placeholder', 'Aguardando...');

            $('#btnCapturarEntrada').prop('disabled', !autoState.conectado);
            $('#btnCapturarSaida').prop('disabled', true);

            $('#badgeModoOperacao').removeClass('badge-warning').addClass('badge-primary').text('NOVO ITEM - PLACA ' + pesagem.placa_veiculo);
            $('#badgeEtapaItem').removeClass('badge-warning').addClass('badge-info').text('NOVO ITEM (1º PESO: ENTRADA)');
            $('#txtBtnSalvarItem').text('Registrar Entrada do Item');
            atualizarSaldoPreview();
        }

        function abrirPesagemParaOperacao(data) {
            renderizarPainelItens(data);

            if (data.status !== 'em andamento') {
                return;
            }

            carregarCabecalhoDaPesagemNoFormulario(data);

            if (parseInt(data.com_itens) === 1) {
                const itens = data.itens || [];
                const itemAberto = itens.find(i => i.status === 'em andamento');

                if (itemAberto) {
                    prepararSaidaDeItem(data, itemAberto);
                } else {
                    prepararNovoItemNoCaminhao(data);
                }
            } else {
                $('#check_com_itens').prop('checked', false).trigger('change');
                let pesoEntradaSalvo = parseFloat(data.peso_bruto) || 0;
                $('#peso_entrada').val(pesoEntradaSalvo.toFixed(2)).prop('readonly', true).addClass('input-travado');
                $('#peso_saida').val('').prop('readonly', BLOQUEAR_MANUAL === 1).removeClass('input-travado').attr('placeholder', '0.00').focus();

                $('#btnCapturarEntrada').prop('disabled', true);
                $('#btnCapturarSaida').prop('disabled', !autoState.conectado);

                $('#badgeModoOperacao').removeClass('badge-primary').addClass('badge-warning').text('MODO: 2ª PESAGEM (SAÍDA) - #' + data.id);
                $('#grupoBotoesEntrada').hide();
                $('#grupoBotoesSaida').show();
                atualizarSaldoPreview();
            }
        }

        $(document).on('click', '.tr-pesagem-row', function() {
            $('.tr-pesagem-row').removeClass('tr-selecionada');
            $(this).addClass('tr-selecionada');
            const pesagem = $(this).data('pesagem');
            if (pesagem) {
                abrirPesagemParaOperacao(pesagem);
            }
        });

        $(document).on('click', '.btn-editar', function(e){
            e.stopPropagation();
            $('.tr-pesagem-row').removeClass('tr-selecionada');
            $(this).closest('tr').addClass('tr-selecionada');
            const data = $(this).data('pesagem');
            if (data) {
                abrirPesagemParaOperacao(data);
            }
        });

        $('#btnPrepararNovoItem').click(function() {
            if (pesagemSelecionadaAtual) {
                prepararNovoItemNoCaminhao(pesagemSelecionadaAtual);
            }
        });

        $(document).on('click', '.btn-pesar-saida-item', function() {
            const item = $(this).data('item');
            if (pesagemSelecionadaAtual && item) {
                prepararSaidaDeItem(pesagemSelecionadaAtual, item);
            }
        });

        $(document).on('click', '.btn-excluir-item', function() {
            const itemId = $(this).data('id');
            swal({
                title: "Remover Item?",
                text: "Deseja excluir este item da pesagem?",
                icon: "warning",
                buttons: ["Não", "Sim, Excluir"],
                dangerMode: true,
            }).then((ok) => {
                if (ok) {
                    $.post('{{ url("pesagens_avulsas/item/delete") }}/' + itemId, { _token: '{{ csrf_token() }}' })
                        .done(res => {
                            renderizarPainelItens(res.pesagem);
                            swal("Removido!", res.message, "success").then(() => location.reload());
                        })
                        .fail(err => swal("Erro", err.responseJSON?.message || "Erro ao remover item.", "error"));
                }
            });
        });

        function abrirImpressao80mm(pesagemId) {
            const ocultarItens = $('#check_imprimir_com_itens').is(':checked') ? 0 : 1;
            window.open('/pesagens_avulsas/imprimir-80mm/' + pesagemId + '?ocultar_itens=' + ocultarItens, '_blank');
        }

        $(document).on('click', '.btn-imprimir-80mm', function(e) {
            e.stopPropagation();
            abrirImpressao80mm($(this).data('id'));
        });

        $(document).on('click', '.btn-cancelar', function(e){
            e.stopPropagation();
            let id = $(this).data('id');
            swal({
                title: "Cancelar Pesagem?",
                text: "Esta pesagem ficará marcada como CANCELADA.",
                icon: "warning",
                buttons: ["Não", "Sim, Cancelar"],
                dangerMode: true,
            }).then((willCancel) => {
                if (willCancel) {
                    $.post('{{ url("pesagens_avulsas/cancelar") }}/' + id, { _token: '{{ csrf_token() }}' })
                        .done(res => { swal("Cancelada!", res.message, "success").then(() => location.reload()); })
                        .fail(() => swal("Erro", "Falha ao cancelar.", "error"));
                }
            });
        });

        $(document).on('click', '.btn-whatsapp', function(e){
            e.stopPropagation();
            let id = $(this).data('id');
            const ocultarItens = $('#check_imprimir_com_itens').is(':checked') ? 0 : 1;
            let urlApp = '{{ url("pesagens_avulsas/imprimir-80mm") }}/' + id + (ocultarItens ? '?ocultar_itens=1' : '');
            let texto = encodeURIComponent("Olá! Segue o ticket da sua pesagem: " + urlApp);
            window.open("https://api.whatsapp.com/send?text=" + texto, "_blank");
        });

        $('#btnBuscarCnpj').click(function() {
            let cnpj = $('#cnpj_busca').val();
            let nome = $('#cliente_nome').val();
            if(!cnpj) return swal("Atenção", "Digite um CNPJ ou CPF.", "warning");
            $(this).html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
            $.post('{{ route("pesagens_avulsas.cliente") }}', { _token: '{{ csrf_token() }}', cnpj: cnpj, cliente_nome: nome })
                .done(res => {
                    $('#cliente_id').val(res.cliente.id);
                    $('#cliente_nome').val(res.cliente.razao_social);
                })
                .fail(() => swal("Erro", "CNPJ/CPF inválido ou erro ao cadastrar.", "error"))
                .always(() => $('#btnBuscarCnpj').html('<i class="fa fa-search"></i>').prop('disabled', false));
        });

        $('#btnBuscarHistorico').click(function() {
            $(this).html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
            $.get('{{ route("pesagens_avulsas.historico") }}', $('#formFiltroHistorico').serialize())
                .done(res => {
                    let html = '';
                    if(res.length === 0) { html = '<tr><td colspan="8">Nenhum registro encontrado.</td></tr>'; }
                    res.forEach(p => {
                        let dataFormatada = new Date(p.created_at).toLocaleString('pt-BR');
                        let statusBadge = p.status == 'concluído' ? '<span class="badge badge-success">FINALIZADO</span>' :
                            (p.status == 'cancelado' ? '<span class="badge badge-danger">CANCELADO</span>' : '<span class="badge badge-warning">ANDAMENTO</span>');
                        let acoes = p.status == 'concluído'
                            ? `<button type="button" class="btn btn-dark btn-action-icon btn-imprimir-80mm" data-id="${p.id}" title="Imprimir"><i class="fa fa-print"></i></button>
                           <button type="button" class="btn btn-success btn-action-icon btn-whatsapp" data-id="${p.id}" title="WhatsApp"><i class="fa fa-whatsapp"></i></button>`
                            : '-';

                        html += `<tr>
                        <td>${dataFormatada}</td>
                        <td class="text-left"><small>${p.cliente ? p.cliente.razao_social : '-'}</small></td>
                        <td>${p.placa_veiculo} - ${p.motorista_nome}</td>
                        <td>${parseInt(p.peso_bruto || 0)}</td>
                        <td>${parseInt(p.tara || 0)}</td>
                        <td class="text-primary">${parseInt(p.peso_liquido || 0)}</td>
                        <td>${statusBadge}</td>
                        <td>${acoes}</td>
                    </tr>`;
                    });
                    $('#tabelaHistoricoBody').html(html);
                })
                .always(() => $('#btnBuscarHistorico').html('<i class="fa fa-search"></i> Pesquisar').prop('disabled', false));
        });

        function iniciarLeituraContinua(balanca) {
            clearInterval(autoState.intervaloLeitura);
            autoState.intervaloLeitura = setInterval(() => {
                if (!autoState.conectado) return;

                window.AdpRuntimeClient.readScale(balanca)
                    .then(dados => {
                        const bruto = parseFloat(dados.peso ?? 0) || 0;
                        $('#pesoAtual').text(bruto.toFixed(2));
                        $('#pesoEstabilidade').text(dados.sobrecarga ? 'Sobrecarga' : (dados.estavel ? 'Estável' : 'Oscilando'));

                        const saidaDestravada = !$('#peso_saida').hasClass('input-travado');
                        const campoAlvo = saidaDestravada ? '#peso_saida' : '#peso_entrada';
                        $(campoAlvo).val(bruto.toFixed(2)).trigger('input');
                    })
                    .catch(err => console.error('Erro ao ler peso ADP:', err));
            }, 250);
        }

        $('#connect').click(function() {
            const balanca = obterBalancaSelecionada();
            if (!balanca || !balanca.id) {
                return swal("Atenção", "Selecione uma balança.", "warning");
            }

            window.AdpRuntimeClient.openScale(balanca)
                .then(() => {
                    autoState.conectado = true;
                    autoState.balancaConectada = balanca;
                    $('#connect').prop('disabled', true);
                    $('#disconnect').prop('disabled', false);
                    $('#balanca-select').prop('disabled', true);

                    const saidaDestravada = !$('#peso_saida').hasClass('input-travado');
                    $('#btnCapturarEntrada').prop('disabled', saidaDestravada);
                    $('#btnCapturarSaida').prop('disabled', !saidaDestravada);

                    iniciarLeituraContinua(balanca);
                })
                .catch(err => swal("Erro", "Erro ao conectar ADP: " + err.message, "error"));
        });

        $('#disconnect').click(function() {
            const balanca = autoState.balancaConectada || obterBalancaSelecionada();
            if (!balanca) return;

            window.AdpRuntimeClient.closeScale(balanca)
                .then(() => {
                    autoState.conectado = false;
                    autoState.balancaConectada = null;
                    clearInterval(autoState.intervaloLeitura);
                    $('#connect').prop('disabled', false);
                    $('#disconnect').prop('disabled', true);
                    $('#balanca-select').prop('disabled', false);
                    $('#btnCapturarEntrada, #btnCapturarSaida').prop('disabled', true);
                    $('#pesoAtual').text('0.00');
                    $('#pesoEstabilidade').text('');
                })
                .catch(err => swal("Erro", "Erro ao desconectar ADP: " + err.message, "error"));
        });

        $('#balanca-select').on('change', function() {
            if (AUTO_CONNECT_BALANCA_AO_SELECIONAR && $(this).val() && !autoState.conectado) {
                $('#connect').trigger('click');
            }
        });

        $('.btn-capturar').click(async function() {
            const target = $(this).data('target');
            const balanca = autoState.balancaConectada || obterBalancaSelecionada();
            if (!balanca) return;

            try {
                const evidence = await window.AdpRuntimeClient.captureEvidence(balanca);
                $('#balanca_evidence_json').val(JSON.stringify(evidence));
                $('#camera_snapshots_json').val(JSON.stringify(evidence.cameras || []));

                const pesoCapturado = (evidence.peso && evidence.peso.valor > 0)
                    ? parseFloat(evidence.peso.valor)
                    : (parseFloat($('#pesoAtual').text()) || 0);

                $('#' + target).val(pesoCapturado.toFixed(2)).trigger('input');
            } catch (err) {
                swal("Erro", "Falha ao capturar peso/evidência: " + err.message, "error");
            }
        });

        async function submeterFormulario(status, acao) {
            if(!$('#cliente_id').val() && !$('#cnpj_busca').val()) {
                return swal("Atenção", "Informe o CNPJ/CPF ou selecione um cliente cadastrado pelo nome.", "warning");
            }
            if(!$('#placa_veiculo').val() || !$('#motorista_nome').val()) {
                return swal("Atenção", "Preencha a Placa e o Nome do Motorista.", "warning");
            }

            if (autoState.conectado && autoState.balancaConectada) {
                try {
                    const evidence = await window.AdpRuntimeClient.captureEvidence(autoState.balancaConectada);
                    if (evidence && evidence.success) {
                        $('#balanca_evidence_json').val(JSON.stringify(evidence));
                        $('#camera_snapshots_json').val(JSON.stringify(evidence.cameras || []));
                    }
                } catch (e) {
                    console.warn('Aviso: falha ao capturar evidência no envio:', e);
                }
            }

            const prodDisabled = $('#produto_id').prop('disabled');
            $('#produto_id').prop('disabled', false);

            $('#status_pesagem').val(status);
            $('#acao_form').val(acao);

            $.post('{{ route("pesagens_avulsas.save") }}', $('#formPesagemAvulsa').serialize())
                .done(res => {
                    swal("Sucesso!", res.message, "success").then(() => {
                        if(res.status === 'concluído') {
                            abrirImpressao80mm(res.pesagem_id);
                        }
                        location.reload();
                    });
                })
                .fail(err => {
                    $('#produto_id').prop('disabled', prodDisabled);
                    swal("Atenção", err.responseJSON?.message || "Falha ao salvar pesagem.", "error");
                });
        }

        $('#btnSalvarEntrada').click(() => submeterFormulario('em andamento', 'salvar_entrada'));
        $('#btnFinalizarDireto').click(() => submeterFormulario('concluído', 'finalizar_direto'));
        $('#btnFinalizarSaida').click(() => submeterFormulario('concluído', 'finalizar_direto'));

        $('#btnSalvarItem').click(() => submeterFormulario('em andamento', 'salvar_item'));
        $('#btnFinalizarComItens').click(() => submeterFormulario('concluído', 'finalizar_pesagem'));
    </script>
@endsection
