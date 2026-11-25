@extends('default.layout')

@section('content')
    <style>
        /* Estilos do PDV Industrial */
        .pdv-container { display: flex; flex-wrap: wrap; gap: 20px; }
        .pdv-captura { flex: 1 1 55%; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .pdv-lateral { flex: 1 1 40%; background: #f8f9fa; padding: 0; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; border: 1px solid #e5e7eb; }
        .pdv-header { background: #3699ff; color: #fff; padding: 15px; border-radius: 8px 8px 0 0; font-weight: bold; font-size: 16px; display: flex; justify-content: space-between; align-items: center; }
        .grid-itens { flex-grow: 1; overflow-y: auto; max-height: 400px; padding: 10px; }
        .grid-itens table { width: 100%; font-size: 13px; }
        .pdv-footer { background: #fff; padding: 15px; border-top: 2px solid #e5e7eb; border-radius: 0 0 8px 8px; }
        .total-acumulado { font-size: 24px; font-weight: bold; color: #1bc5bd; text-align: right; margin-bottom: 15px; }

        .display-peso { font-size: 32px; font-weight: bold; color: #212121; background: #e4e6ef; border: none; text-align: right; height: 60px; border-radius: 8px; }
    </style>

    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label"><i class="fa fa-balance-scale text-primary"></i> Balança Cega de Produção & Corte</h3>
            </div>
        </div>
        <div class="card-body">

            <div class="pdv-container">
                <!-- COLUNA ESQUERDA: CAPTURA DE PESO -->
                <div class="pdv-captura">
                    <form id="formCapturaPeso">
                        @csrf

                        <!-- Lote Mãe -->
                        <div class="form-group">
                            <label class="font-weight-bold text-primary">1. Lote em Processamento:</label>
                            <div class="input-group">
                                <select id="producao_lote_id" name="producao_lote_id" class="form-control select2" required>
                                    <option value="">-- Selecione o Lote --</option>
                                    @foreach($lotesAbertos as $lote)
                                        <option value="{{ $lote->id }}">
                                            {{ $lote->codigo_lote }} | Entrada: {{ \Carbon\Carbon::parse($lote->created_at)->format('d/m/Y') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-warning" id="btnTrocarLote" style="display:none;" title="Trocar de Lote">
                                        <i class="fa fa-unlock"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Produto Gerado -->
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">2. Produto Gerado:</label>
                                <select id="produto_id" name="produto_id" class="form-control select2" required>
                                    <option value="">-- Selecione o Produto --</option>
                                    @foreach($produtosGerados as $prod)
                                        <option value="{{ $prod->id }}">{{ $prod->nome }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Funcionário / Ajudante -->
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">3. Operador / Ajudante (Opcional):</label>
                                <select id="funcionario_id" name="funcionario_id" class="form-control select2">
                                    <option value="">-- Selecione --</option>
                                    @foreach($funcionarios as $func)
                                        <option value="{{ $func->id }}">{{ $func->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr>

                        <!-- Módulo de Integração ADP (Balança) -->
                        <div class="form-group">
                            <label class="font-weight-bold">4. Balança do Galpão:</label>
                            <div class="input-group">
                                <select id="balanca_config_id" name="balanca_config_id" class="form-control">
                                    <option value="">-- Selecionar Balança --</option>
                                    @foreach($balancas as $balanca)
                                        <option value="{{ $balanca->id }}"
                                                data-backend="{{ $balanca->backend_server_address }}"
                                                data-modelo="{{ $balanca->modelo }}"
                                                data-port="{{ $balanca->port ?? $balanca->porta_serial }}"
                                                data-integrador="{{ $balanca->integrador ?? 'adp' }}"
                                                data-integrador-config-id="{{ $balanca->integrador_config_id ?? '' }}"
                                                data-adp-scale-uuid="{{ $balanca->adp_scale_uuid ?? '' }}">
                                            {{ $balanca->descricao }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button id="btnConectarBalanca" type="button" class="btn btn-success btn-sm"><i class="fa fa-link"></i> Conectar</button>
                                    <button id="btnDesconectarBalanca" type="button" class="btn btn-danger btn-sm" disabled><i class="fa fa-unlink"></i> Desconectar</button>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-5">
                                <label class="font-weight-bold">Peso Bruto (kg):</label>
                                <input type="number" id="peso" name="peso" step="0.01" class="form-control display-peso" value="0.00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="font-weight-bold">Tara Bag (kg):</label>
                                <input type="number" id="peso_bag" name="peso_bag" step="0.01" class="form-control" value="0.00" style="height: 60px; font-size: 20px; text-align: center;">
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold text-success">Líquido (kg):</label>
                                <input type="number" id="peso_liquido" step="0.01" class="form-control display-peso bg-light-success text-success" value="0.00" readonly>
                            </div>
                        </div>

                        <div class="text-right mt-5">
                            <span id="statusEstabilidade" class="badge badge-secondary p-2 mr-3">Aguardando...</span>
                            <button type="submit" id="btnRegistrarPesagem" class="btn btn-primary btn-lg" disabled>
                                <i class="fa fa-plus-circle"></i> Registrar Pesagem
                            </button>
                        </div>
                    </form>
                </div>

                <!-- COLUNA DIREITA: GRID LATERAL -->
                <div class="pdv-lateral">
                    <div class="pdv-header">
                        <span><i class="fa fa-list"></i> Itens do Lote Selecionado</span>
                        <span id="lblLoteAtivo" class="badge badge-light text-primary">Nenhum Lote</span>
                    </div>

                    <div class="grid-itens">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="thead-light" style="position: sticky; top: 0;">
                            <tr>
                                <th>Qtd</th>
                                <th>Produto</th>
                                <th>Tara</th>
                                <th>Líquido</th>
                                <th width="10%">Ação</th>
                            </tr>
                            </thead>
                            <tbody id="tabelaItensLote">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Selecione um lote para carregar as pesagens.</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pdv-footer">
                        <div class="total-acumulado">
                            Total Liquido: <span id="lblTotalLiquido">0,00</span> kg
                        </div>
                        <button type="button" id="btnFinalizarLote" class="btn btn-success btn-block btn-lg font-weight-bold" disabled>
                            <i class="fa fa-check-circle"></i> FINALIZAR LOTE E IMPRIMIR
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('javascript')

    <!-- Scripts Básicos (Axios, SweetAlert, Select2) -->
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/adp-runtime-client.js') }}"></script>

    <script>
        // Carrega configurações globais da tabela config_notas
        const BLOQUEAR_PESAGEM_MANUAL_BALANCA = {{ (int) ($configNota->bloquear_pesagem_manual_balanca ?? 0) }};
        const DESBLOQUEAR_CAMPO_PESO_BAG_TICKET = {{ (int) ($configNota->desbloquear_campo_peso_bag_ticket ?? 0) }};
        const AUTO_CONNECT_BALANCA_PADRAO_USUARIO = {{ (int) ($configNota->conectar_automaticamente_balanca_padrao_usuario ?? 0) }};
        const AUTO_CONNECT_BALANCA_AO_SELECIONAR = {{ (int) ($configNota->conectar_automaticamente_balanca_ao_selecionar ?? 0) }};
        const BALANCA_PADRAO_USUARIO_ID = {{ (int) ($balancaPadraoUsuarioId ?? 0) }};

        // Estado Global da Balança ADP
        let balancaSelecionada = null;
        let intervaloLeitura = null;
        let conectado = false;

        // Formatação Select2
        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });

            // Aplica bloqueio manual se configurado
            if (BLOQUEAR_PESAGEM_MANUAL_BALANCA) {
                $('#peso').prop('readonly', true);
            }
            if (!DESBLOQUEAR_CAMPO_PESO_BAG_TICKET) {
                $('#peso_bag').prop('readonly', true);
            }

            // Auto-seleciona a balança padrão do usuário se existir
            if (BALANCA_PADRAO_USUARIO_ID > 0) {
                $('#balanca_config_id').val(BALANCA_PADRAO_USUARIO_ID).trigger('change');
                if (AUTO_CONNECT_BALANCA_PADRAO_USUARIO) {
                    setTimeout(() => $('#btnConectarBalanca').click(), 500);
                }
            }

            // Ação: Ao trocar o Lote Mãe, busca os itens vinculados a ele
            $('#producao_lote_id').on('change', function() {
                const loteId = $(this).val();

                if (loteId) {
                    $(this).prop('disabled', true); // Trava o lote
                    $('#btnTrocarLote').show();
                    carregarItensLote(loteId);
                } else {
                    $('#tabelaItensLote').html('<tr><td colspan="5" class="text-center text-muted py-4">Selecione um lote para carregar as pesagens.</td></tr>');
                    $('#lblTotalLiquido').text('0,00');
                    $('#lblLoteAtivo').text('Nenhum Lote');
                }
            });

            // Ação: Destravar Lote
            $('#btnTrocarLote').on('click', function() {
                $('#producao_lote_id').prop('disabled', false).val('').trigger('change');
                $(this).hide();
                $('#btnFinalizarLote').prop('disabled', true);
            });

            // Controle da Balança ADP - Seleção
            $('#balanca_config_id').on('change', function() {
                if (conectado) {
                    $('#btnDesconectarBalanca').click(); // Força desconexão antes de trocar
                }
                const $opt = $(this).find(':selected');
                if ($opt.val()) {
                    balancaSelecionada = {
                        id: $opt.val(),
                        backend: $opt.data('backend')
                    };
                    if (AUTO_CONNECT_BALANCA_AO_SELECIONAR) {
                        $('#btnConectarBalanca').click();
                    }
                } else {
                    balancaSelecionada = null;
                }
            });

            // Controle da Balança ADP - Conectar
            $('#btnConectarBalanca').on('click', function() {
                if (!balancaSelecionada) {
                    swal("Aviso", "Selecione uma balança primeiro!", "warning");
                    return;
                }

                window.AdpRuntimeClient.openScale(balancaSelecionada)
                    .then(() => {
                        conectado = true;
                        $(this).prop('disabled', true);
                        $('#btnDesconectarBalanca').prop('disabled', false);
                        $('#balanca_config_id').prop('disabled', true);
                        $('#statusEstabilidade').removeClass('badge-secondary badge-danger').addClass('badge-success').text('Conectado');

                        // Loop de leitura
                        intervaloLeitura = setInterval(() => {
                            window.AdpRuntimeClient.readScale(balancaSelecionada)
                                .then(dados => {
                                    const bruto = parseFloat(dados.peso || 0);
                                    const taraBag = parseFloat($('#peso_bag').val() || 0);

                                    $('#peso').val(bruto.toFixed(2));
                                    $('#peso_liquido').val(Math.max(0, bruto - taraBag).toFixed(2));

                                    if (dados.estavel) {
                                        $('#statusEstabilidade').removeClass('badge-warning').addClass('badge-success').text('Estável');
                                        $('#btnRegistrarPesagem').prop('disabled', false);
                                    } else {
                                        $('#statusEstabilidade').removeClass('badge-success').addClass('badge-warning').text('Oscilando');
                                        $('#btnRegistrarPesagem').prop('disabled', true);
                                    }
                                })
                                .catch(err => console.error("Erro leitura ADP:", err));
                        }, 300);
                    })
                    .catch(err => {
                        swal("Erro", "Não foi possível conectar: " + err.message, "error");
                    });
            });

            // Controle da Balança ADP - Conectar
            $('#btnConectarBalanca').off('click').on('click', function() {
                const $opt = $('#balanca_config_id').find(':selected');
                const balancaId = $opt.val();

                if (!balancaId) {
                    swal("Aviso", "Selecione uma balança primeiro!", "warning");
                    return;
                }

                // Monta o objeto da balança na hora do clique (Garante que nunca fique nulo)
                balancaSelecionada = {
                    id: balancaId,
                    backend: $opt.data('backend'),
                    modelo: $opt.data('modelo'),
                    porta: $opt.data('port'),
                    integrador: $opt.data('integrador') || 'adp',
                    integrador_config_id: $opt.data('integrador-config-id') || '',
                    adp_scale_uuid: $opt.data('adp-scale-uuid') || '',
                    descricao: $opt.text().trim()
                };

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Conectando...');

                window.AdpRuntimeClient.openScale(balancaSelecionada)
                    .then(() => {
                        conectado = true;
                        $btn.html('<i class="fa fa-link"></i> Conectado'); // Mantém desabilitado, mas muda o texto
                        $('#btnDesconectarBalanca').prop('disabled', false);
                        $('#balanca_config_id').prop('disabled', true);
                        $('#statusEstabilidade').removeClass('badge-secondary badge-danger badge-warning').addClass('badge-success').text('Conectado');

                        // Loop de leitura contínua
                        intervaloLeitura = setInterval(() => {
                            window.AdpRuntimeClient.readScale(balancaSelecionada)
                                .then(dados => {
                                    const bruto = parseFloat(dados.peso || 0);
                                    const taraBag = parseFloat($('#peso_bag').val() || 0);
                                    const liquido = Math.max(0, bruto - taraBag);

                                    $('#peso').val(bruto.toFixed(2));
                                    $('#peso_liquido').val(liquido.toFixed(2));

                                    if (dados.estavel) {
                                        $('#statusEstabilidade').removeClass('badge-warning').addClass('badge-success').text('Estável');

                                        // Só libera o botão de registrar se houver peso e o Lote/Produto estiverem preenchidos
                                        if (liquido > 0 && $('#producao_lote_id').val() && $('#produto_id').val()) {
                                            $('#btnRegistrarPesagem').prop('disabled', false);
                                        } else {
                                            $('#btnRegistrarPesagem').prop('disabled', true);
                                        }
                                    } else {
                                        $('#statusEstabilidade').removeClass('badge-success').addClass('badge-warning').text('Oscilando');
                                        $('#btnRegistrarPesagem').prop('disabled', true);
                                    }
                                })
                                .catch(err => console.error("Erro leitura ADP:", err));
                        }, 300);
                    })
                    .catch(err => {
                        $btn.prop('disabled', false).html('<i class="fa fa-link"></i> Conectar');
                        swal("Erro", "Não foi possível conectar: " + err.message, "error");
                    });
            });

            // Cálculo manual de Tara
            $('#peso_bag, #peso').on('input', function() {
                const b = parseFloat($('#peso').val() || 0);
                const t = parseFloat($('#peso_bag').val() || 0);
                $('#peso_liquido').val(Math.max(0, b - t).toFixed(2));

                if (!BLOQUEAR_PESAGEM_MANUAL_BALANCA && $('#producao_lote_id').val() && $('#produto_id').val()) {
                    $('#btnRegistrarPesagem').prop('disabled', false);
                }
            });

            // Ação: Salvar Pesagem Parcial (Item)
            $('#formCapturaPeso').off('submit').on('submit', function(e) {
                e.preventDefault();

                const loteId = $('#producao_lote_id').val();
                if (!loteId) {
                    swal("Aviso", "Selecione o Lote em Processamento.", "warning");
                    return;
                }

                // Captura os dados da tela (desabilitados precisam do 'disabled=false' temporário no serialize)
                $('#producao_lote_id').prop('disabled', false);
                const formData = $(this).serialize();
                $('#producao_lote_id').prop('disabled', true);

                const $btn = $('#btnRegistrarPesagem');
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Registrando...');

                $.post('{{ route("producao.balanca.item.save") }}', formData)
                    .done(function(res) {
                        if (res.success) {
                            carregarItensLote(loteId); // Atualiza grid lateral
                            $('#peso').val('0.00');
                            // A TARA NÃO É MAIS ZERADA AQUI: ela permanece fixa para a próxima leitura!
                            $('#peso_liquido').val('0.00');
                        } else {
                            swal("Erro", res.error, "error");
                        }
                    })
                    .fail(function() {
                        swal("Erro", "Falha de comunicação ao salvar pesagem.", "error");
                    })
                    .always(function() {
                        $btn.html('<i class="fa fa-plus-circle"></i> Registrar Pesagem');
                    });
            });

            // 1. Ação: Excluir Pesagem Parcial (COM SENHA DE SEGURANÇA MASCARADA)
            $(document).on('click', '.btn-excluir-item', function() {
                const itemId = $(this).data('id');
                const loteId = $('#producao_lote_id').val();

                swal({
                    title: "ACESSO RESTRITO",
                    text: "Digite a senha de liberação para excluir esta pesagem:",
                    icon: "warning",
                    content: {
                        element: "input",
                        attributes: {
                            placeholder: "Digite a senha",
                            type: "password", // Isso garante que a senha fique oculta (***)
                        },
                    },
                    buttons: ["Cancelar", "Confirmar"],
                }).then((senha) => {
                    if (senha === null) return; // O operador clicou em cancelar

                    if (senha === "1982") {
                        $.ajax({
                            url: `/producao/balanca/item/${itemId}`,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                carregarItensLote(loteId); // Atualiza o grid
                                swal("Excluído!", "A pesagem parcial foi removida.", "success");
                            }
                        });
                    } else {
                        swal("Acesso Negado", "Senha incorreta!", "error");
                    }
                });
            });

            // 2. Ação: Finalizar Lote (Sem popup do WhatsApp Web)
            $('#btnFinalizarLote').off('click').on('click', function() {
                const loteId = $('#producao_lote_id').val();
                if (!loteId) return;

                swal({
                    title: "Finalizar Ticket e Imprimir?",
                    text: "Isso atualizará o estoque e gerará o ticket de impressão.",
                    icon: "info",
                    buttons: ["Cancelar", "Sim, Finalizar!"],
                }).then((confirma) => {
                    if (confirma) {
                        const $btn = $(this);
                        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processando Fechamento e Estoque...');

                        $.post(`/producao/balanca/finalizar/${loteId}`, { _token: '{{ csrf_token() }}' })
                            .done(function(res) {
                                if (res.success) {
                                    swal("Sucesso!", res.message, "success").then(() => {
                                        const urlTicket = `${window.location.origin}/producao/balanca/ticket/${loteId}?ticket=${res.ticket_numero}`;
                                        window.open(urlTicket, '_blank', 'width=450,height=700');
                                        location.reload();
                                    });
                                } else {
                                    swal("Aviso", res.error || "Erro ao processar.", "warning");
                                    $btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> FINALIZAR LOTE E IMPRIMIR');
                                }
                            })
                            .fail(function(xhr) {
                                const msg = (xhr.responseJSON && xhr.responseJSON.error)
                                    ? xhr.responseJSON.error
                                    : "Falha de comunicação ao finalizar lote.";
                                swal("Aviso", msg, "warning");
                                $btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> FINALIZAR LOTE E IMPRIMIR');
                            });
                    }
                });
            });
        });

        // Função de Atualização do Grid Lateral
        function carregarItensLote(loteId) {
            $('#lblLoteAtivo').text($('#producao_lote_id option:selected').text());

            $.get(`/producao/balanca/itens/${loteId}`, function(res) {
                if (res.success && res.itens.length > 0) {
                    let linhas = '';
                    res.itens.forEach((item, idx) => {
                        linhas += `
                            <tr>
                                <td class="text-center font-weight-bold">${res.itens.length - idx}</td>
                                <td>${item.produto ? item.produto.nome : '--'}<br><small class="text-muted">${item.funcionario ? item.funcionario.nome : ''}</small></td>
                                <td class="text-center text-danger">${parseFloat(item.peso_bag).toFixed(2)}</td>
                                <td class="text-center text-success font-weight-bold">${parseFloat(item.peso_liquido).toFixed(2)} kg</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger btn-excluir-item" data-id="${item.id}">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    $('#tabelaItensLote').html(linhas);
                    $('#lblTotalLiquido').text(parseFloat(res.total_liquido).toFixed(2).replace('.', ','));
                    $('#btnFinalizarLote').prop('disabled', false); // Libera o botão apenas se houver itens
                } else {
                    $('#tabelaItensLote').html('<tr><td colspan="5" class="text-center text-muted py-4">Nenhuma pesagem registrada neste lote.</td></tr>');
                    $('#lblTotalLiquido').text('0,00');
                    $('#btnFinalizarLote').prop('disabled', true); // Mantém desabilitado se vazio
                }
            });
        }
    </script>
@endsection
