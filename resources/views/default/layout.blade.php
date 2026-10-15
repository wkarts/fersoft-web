<style type="text/css">
    .select2-selection__choice__remove::before{
        display: none
    }
</style>

@php
    use Carbon\Carbon;

    // --- BLOCO DE ALERTAS INTELIGENTES DE TAREFAS ---
    $usuarioLogadoId = session('user_logged')['id'] ?? null;
    $alertaTarefa = null;
    $tipoAlerta = 'info';

    if ($usuarioLogadoId) {
        $funcionario = \App\Models\Funcionario::where('usuario_id',$usuarioLogadoId)->first();

        if ($funcionario) {
            $tarefaDeHoje = \App\Models\Tarefa::where('funcionario_id',$funcionario->id)
                ->where('status', 'pendente')
                ->whereDate('data', Carbon::today())
                ->whereNotNull('hora_estimada')
                ->first();

            if ($tarefaDeHoje) {
                $agora = Carbon::now();$horarioTarefa = Carbon::parse(Carbon::today()->format('Y-m-d') . ' ' . $tarefaDeHoje->hora_estimada);$diferencaMinutos = $agora->diffInMinutes($horarioTarefa, false);

                if ($diferencaMinutos > 0 &&$diferencaMinutos <= 15) {
                    $tipoAlerta = 'info';$alertaTarefa = "⏱️ <b>Próxima Tarefa:</b> Você tem uma atividade agendada para iniciar em breve (<b>{$tarefaDeHoje->titulo}</b> às " . Carbon::parse($tarefaDeHoje->hora_estimada)->format('H:i') . "). Prepare as ferramentas necessárias e lembre-se de dar o 'Play' assim que começar!";
                }
                elseif ($diferencaMinutos < 0) {
                    $tipoAlerta = 'danger';$alertaTarefa = "🚨 <b>Aviso de Início:</b> A atividade <b>{$tarefaDeHoje->titulo}</b> estava agendada para as " . Carbon::parse($tarefaDeHoje->hora_estimada)->format('H:i') . " e ainda não foi iniciada. Vá até a rotina para dar o início e não se esqueça de finalizá-la ao terminar.";
                }
            }
        }
    }
@endphp


{{-- Complemento da versão nova: resumo de tarefas críticas por empresa. --}}
@php
    $userLoggedAlertas = session('user_logged') ?: [];
    $usuarioAlertasId = $userLoggedAlertas['id'] ?? $userLoggedAlertas['usuario_id'] ?? null;
    $usuarioAlertas = $usuarioAlertasId ? \App\Models\Usuario::find($usuarioAlertasId) : null;
    $empresaAlertasId = $usuarioAlertas->empresa_id ?? ($userLoggedAlertas['empresa_id'] ?? null);
    $isAdmAlertas = $usuarioAlertas && (
        (int) $usuarioAlertas->adm === 1 || (int) ($usuarioAlertas->is_admin ?? 0) === 1
    );
    $tarefasVencidasAlerta = collect();
    $tarefasNaoIniciadasAlerta = collect();

    if ($usuarioAlertasId && $empresaAlertasId && !session('alerta_tarefas_visto')) {
        $queryBaseAlertas = \App\Models\Tarefa::with('funcionario')
            ->where('empresa_id', $empresaAlertasId)
            ->whereNotIn('status', ['concluida', 'concluido', 'finalizada', 'cancelada', 'cancelado']);

        if (!$isAdmAlertas) {
            $funcionarioAlertas = \App\Models\Funcionario::where('empresa_id', $empresaAlertasId)
                ->where('usuario_id', $usuarioAlertasId)
                ->first();
            $funcIdAlertas = $funcionarioAlertas->id ?? 0;
            $queryBaseAlertas->where(function ($q) use ($usuarioAlertasId, $funcIdAlertas) {
                $q->where('usuario_id', $usuarioAlertasId)
                  ->orWhere('funcionario_id', $funcIdAlertas);
            });
        }

        $agoraAlertas = Carbon::now();
        foreach ($queryBaseAlertas->get() as $t) {
            $statusReal = strtolower(trim((string) $t->status));
            $isAtrasada = false;

            if (!empty($t->data_limite)) {
                $dtFimStr = Carbon::parse(str_replace('/', '-', $t->data_limite))->format('Y-m-d');
                $hrFimStr = !empty($t->hora_limite) ? $t->hora_limite : '23:59:59';
                $momentoLimite = Carbon::parse($dtFimStr . ' ' . $hrFimStr);
                if ($agoraAlertas->greaterThan($momentoLimite)) {
                    $isAtrasada = true;
                    $t->tipo_aviso = 'atrasada';
                    $tarefasVencidasAlerta->push($t);
                }
            }

            if (!$isAtrasada && in_array($statusReal, ['pendente', 'aberta', ''], true)
                && !empty($t->data)) {
                $dtIniStr = Carbon::parse(str_replace('/', '-', $t->data))->format('Y-m-d');
                $hrIniStr = !empty($t->hora_estimada) ? $t->hora_estimada : '00:00:00';
                $momentoInicio = Carbon::parse($dtIniStr . ' ' . $hrIniStr);
                if ($agoraAlertas->greaterThanOrEqualTo($momentoInicio)) {
                    $t->tipo_aviso = 'nao_iniciada';
                    $tarefasNaoIniciadasAlerta->push($t);
                }
            }
        }
    }

    $totalAtrasadasAlerta = $tarefasVencidasAlerta->count();
    $totalNaoIniciadasAlerta = $tarefasNaoIniciadasAlerta->count();
    $listaTarefasCriticas = $tarefasVencidasAlerta
        ->merge($tarefasNaoIniciadasAlerta)
        ->take(10);
@endphp

{{-- 1. DEFINE O LAYOUT PAI NO TOPO --}}
@extends(config('ui_theme.active', false) ? 'themes.refined.layout' : 'default/menu_'.$tipoMenu)

{{-- 2. ALERTA DE TAREFAS NO TOPO DA TELA --}}
@if($alertaTarefa)
    <div class="container-fluid pt-3 px-4">
        <div class="alert alert-{{ $tipoAlerta }} d-flex justify-content-between align-items-center shadow-sm border-0 border-start border-4 {{$tipoAlerta == 'danger' ? 'border-danger' : 'border-info' }} mb-0" role="alert">
            <div class="d-flex align-items-center">
                <span class="fs-5 me-3">
                    {!! $tipoAlerta == 'danger' ? '⚠️' : '🔔' !!}
                </span>
                <span class="text-dark">{!! $alertaTarefa !!}</span>
            </div>
            <a href="/tarefas/painel" class="btn btn-sm {{ $tipoAlerta == 'danger' ? 'btn-danger' : 'btn-info text-white' }} fw-bold text-nowrap ms-3 shadow-sm">
                <i class="fas fa-arrow-right me-1"></i> Ir para o Painel
            </a>
        </div>
    </div>
@endif

{{-- 3. MODAL E MONITORAMENTO DE NOVOS PEDIDOS DO DELIVERY --}}
@section('global_delivery_alert')
    <div class="modal fade" id="modalNovoPedidoAlerta" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true" style="display:none; z-index: 99999;">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title font-weight-bold text-white">
                        <i class="la la-bell mr-2"></i> Novo Pedido de Delivery Recebido!
                    </h5>
                </div>
                <div class="modal-body p-4">
                    <div id="detalhesNovoPedidoConteudo">
                        <p class="text-muted">Carregando informações do pedido...</p>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-danger font-weight-bold px-4" id="btnRecusarPedidoModal">
                        <i class="la la-times"></i> Recusar Pedido
                    </button>
                    <button type="button" class="btn btn-success font-weight-bold px-4" id="btnAceitarPedidoModal">
                        <i class="la la-check"></i> Aceitar e Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            if (typeof window.jQuery === 'undefined') {
                console.error('jQuery não carregado: monitor de pedidos do delivery não iniciado.');
                return;
            }

            const $ = window.jQuery;
            let pedidoPendenteAtualId = null;
            let modalAberto = false;

            function exibirErroPedido(mensagem) {
                if (typeof swal === 'function') {
                    swal("Atenção!", mensagem, "error");
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(mensagem);
                } else {
                    alert(mensagem);
                }
            }

            let csrfDeliveryAtual = '{{ csrf_token() }}';

            function obterCsrfDelivery(callback) {
                $.ajax({
                    url: '/pedidosDelivery/csrf-token',
                    type: 'GET',
                    dataType: 'json',
                    cache: false
                })
                    .done(function(response) {
                        if (response && response.token) {
                            csrfDeliveryAtual = response.token;
                        }
                        callback(csrfDeliveryAtual);
                    })
                    .fail(function() {
                        callback(csrfDeliveryAtual);
                    });
            }

            function enviarStatusPedido(estado, motivo, permitirRenovacaoCsrf) {
                const $botoes = $('#btnAceitarPedidoModal, #btnRecusarPedidoModal');

                $.ajax({
                    url: '/pedidosDelivery/actualizarStatusKanban',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfDeliveryAtual
                    },
                    data: {
                        _token: csrfDeliveryAtual,
                        id: pedidoPendenteAtualId,
                        estado: estado,
                        motivo: motivo || ''
                    },
                    success: function(response) {
                        if (!response || response.sucesso !== true) {
                            exibirErroPedido(response && response.mensagem
                                ? response.mensagem
                                : 'Não foi possível atualizar o pedido.');
                            $botoes.prop('disabled', false);
                            return;
                        }

                        $('#modalNovoPedidoAlerta').modal('hide');
                        modalAberto = false;

                        if (typeof toastr !== 'undefined') {
                            if (estado === 'cancelado') {
                                toastr.info('O pedido foi recusado.');
                            } else {
                                toastr.success('Pedido aceito com sucesso!');
                            }
                        }

                        if (estado === 'aprovado') {
                            setTimeout(function() {
                                window.location.href = '/pedidosDelivery/kanban';
                            }, 500);
                        } else {
                            pedidoPendenteAtualId = null;
                            $botoes.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 419 && permitirRenovacaoCsrf !== false) {
                            obterCsrfDelivery(function() {
                                enviarStatusPedido(estado, motivo, false);
                            });
                            return;
                        }

                        let mensagem = xhr.status === 419
                            ? 'Sua sessão foi atualizada. Recarregue a página e tente novamente.'
                            : 'Erro ao atualizar o pedido.';

                        if (xhr.responseJSON && xhr.responseJSON.mensagem) {
                            mensagem = xhr.responseJSON.mensagem;
                        }

                        exibirErroPedido(mensagem);
                        $botoes.prop('disabled', false);
                    }
                });
            }

            function alterarStatusPedido(estado, motivo) {
                if (!pedidoPendenteAtualId) return;

                const $botoes = $('#btnAceitarPedidoModal, #btnRecusarPedidoModal');
                $botoes.prop('disabled', true);

                obterCsrfDelivery(function() {
                    enviarStatusPedido(estado, motivo, true);
                });
            }

            setInterval(function() {
                if (modalAberto) return;

                $.ajax({
                    url: '/pedidosDelivery/ultimoPedidoNovo',
                    type: 'GET',
                    success: function(response) {
                        if (!response || !response.id || response.id === pedidoPendenteAtualId) {
                            return;
                        }

                        pedidoPendenteAtualId = response.id;
                        modalAberto = true;

                        let itensHtml = '<ul>';
                        (response.itens || []).forEach(function(item) {
                            const produto = item.produto && item.produto.nome
                                ? item.produto.nome
                                : 'Item';

                            itensHtml += '<li>' +
                                item.quantidade + 'x ' + produto +
                                ' - R$ ' + (item.valor || '0,00') +
                                '</li>';
                        });
                        itensHtml += '</ul>';

                        const cliente = response.cliente || {};
                        const htmlInfo = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong># Pedido:</strong> #${response.id}</p>
                            <p><strong>Cliente:</strong> ${cliente.nome || 'Cliente Web'}</p>
                            <p><strong>Telefone:</strong> ${cliente.telefone || '--'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Valor Total:</strong> <span class="text-success font-weight-bold">R$ ${response.valor_total || response.valor || '0,00'}</span></p>
                            <p><strong>Pagamento:</strong> ${response.forma_pagamento_label || response.forma_pagamento || '--'}</p>
                            <p><strong>Tipo de Entrega:</strong> ${response.tipo_entrega || '--'}</p>
                        </div>
                    </div>
                    ${response.endereco ? '<p class="mb-2"><strong>Endereço:</strong> ' + response.endereco + '</p>' : ''}
                    ${response.observacao ? '<p class="mb-2"><strong>Observação:</strong> ' + response.observacao + '</p>' : ''}
                    <hr>
                    <p><strong>Itens do Pedido:</strong></p>
                    ${itensHtml}
                `;

                        $('#detalhesNovoPedidoConteudo').html(htmlInfo);
                        $('#modalNovoPedidoAlerta').modal({
                            backdrop: 'static',
                            keyboard: false,
                            show: true
                        });

                        if (typeof audioSuccess === 'function') {
                            audioSuccess();
                        }
                    },
                    error: function(xhr) {
                        console.error('Erro ao consultar novo pedido do delivery:', xhr);
                    }
                });
            }, 10000);

            $('#btnAceitarPedidoModal').on('click', function() {
                alterarStatusPedido('aprovado', '');
            });

            $('#btnRecusarPedidoModal').on('click', function() {
                swal({
                    title: "Deseja realmente recusar este pedido?",
                    text: "Informe o motivo da recusa:",
                    content: "input",
                    icon: "warning",
                    buttons: ["Cancelar", "Confirmar Recusa"],
                    dangerMode: true,
                }).then(function(motivo) {
                    if (motivo) {
                        alterarStatusPedido('cancelado', motivo);
                    }
                });
            });
        });
    </script>

    {{-- Complementos renderizados na mesma seção já usada pelos menus da base. --}}
    <!-- 1. MODAL OBRIGATÓRIO DE TAREFAS ATRASADAS / PENDENTES -->
    @if(($totalAtrasadasAlerta > 0 || $totalNaoIniciadasAlerta > 0) && !session('alerta_tarefas_visto'))
        <div class="modal fade" id="modalAlertaTarefasEquipe" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 10500;">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                    <div class="modal-header {{ $totalAtrasadasAlerta > 0 ? 'bg-danger' : 'bg-warning' }} text-white py-4 px-5">
                        <div class="d-flex align-items-center">
                            <i class="la la-bell fs-1 text-white mr-3"></i>
                            <div>
                                <h4 class="modal-title font-weight-bolder text-white mb-0">
                                    {{ $isAdmAlertas ? 'Painel de Alerta: Tarefas da Equipe' : 'Atenção: Suas Tarefas Pendentes' }}
                                </h4>
                                <small class="text-white opacity-90">
                                    {{ $isAdmAlertas ? 'Visão Geral Administrativa de Produtividade' : 'Organize sua rotina para evitar atrasos' }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body p-5">
                        <!-- CARDS DE RESUMO -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6 col-12 mb-2">
                                <div class="p-3 rounded border d-flex align-items-center bg-light-danger">
                            <span class="btn btn-danger btn-icon mr-3 font-weight-bolder fs-4" style="width: 45px; height: 45px;">
                                {{ $totalAtrasadasAlerta }}
                            </span>
                                    <div>
                                        <h6 class="font-weight-bolder text-danger mb-0">Tarefas Vencidas / Atrasadas</h6>
                                        <small class="text-muted">Precisam de conclusão imediata</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mb-2">
                                <div class="p-3 rounded border d-flex align-items-center bg-light-warning">
                            <span class="btn btn-warning btn-icon mr-3 font-weight-bolder fs-4" style="width: 45px; height: 45px;">
                                {{ $totalNaoIniciadasAlerta }}
                            </span>
                                    <div>
                                        <h6 class="font-weight-bolder text-warning mb-0">Tarefas Pendentes</h6>
                                        <small class="text-muted">Aguardando início de execução</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- LISTA DE TAREFAS -->
                        <h6 class="font-weight-bold text-dark mb-3"><i class="la la-list-alt text-primary mr-1"></i> Principais atividades que requerem atenção:</h6>
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th>#ID</th>
                                    <th>Tarefa</th>
                                    @if($isAdmAlertas) <th>Responsável</th> @endif
                                    <th>Situação</th>
                                    <th>Prazo Limite</th>
                                    <th>Prioridade</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($listaTarefasCriticas as $tc)
                                    <tr>
                                        <td class="font-weight-bold text-muted">#{{ $tc->id }}</td>
                                        <td class="font-weight-bold text-dark">{{ $tc->titulo }}</td>
                                        @if($isAdmAlertas)
                                            <td><span class="badge badge-light-primary">{{ $tc->funcionario->nome ?? 'Não atribuído' }}</span></td>
                                        @endif
                                        <td>
                                            @if($tc->tipo_aviso === 'atrasada')
                                                <span class="badge badge-danger">⚠️ Vencida</span>
                                            @else
                                                <span class="badge badge-warning text-dark">⏱ Não Iniciada</span>
                                            @endif
                                        </td>
                                        <td class="{{ $tc->tipo_aviso === 'atrasada' ? 'text-danger font-weight-bolder' : 'text-dark' }}">
                                            @if(!empty($tc->data_limite))
                                                {{ \Carbon\Carbon::parse($tc->data_limite)->format('d/m/Y') }}
                                                {{ $tc->hora_limite ? ' às ' . substr($tc->hora_limite, 0, 5) : '' }}
                                            @else
                                                <span class="text-muted">Sem prazo definido</span>
                                            @endif
                                        </td>
                                        <td>
                                    <span class="badge {{ $tc->prioridade == 'Urgente' ? 'badge-danger' : ($tc->prioridade == 'Alta' ? 'badge-warning text-dark' : 'badge-primary') }}">
                                        {{ $tc->prioridade }}
                                    </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-3 px-5 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary font-weight-bold" id="btnEstouCienteTarefas">
                            <i class="la la-check"></i> Estou Ciente / Fechar
                        </button>
                        <div>
                            <a href="/tarefas/painel" class="btn btn-primary font-weight-bold shadow-sm mr-2">
                                <i class="la la-columns"></i> Abrir Painel Kanban
                            </a>
                            <a href="/tarefas" class="btn btn-outline-dark font-weight-bold shadow-sm">
                                <i class="la la-list"></i> Ver Lista
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            window.addEventListener('load', function() {
                if (!window.jQuery) return;
                setTimeout(function() {
                    if (!$('#modalNovoPedidoAlerta').hasClass('show')) {
                        $('#modalAlertaTarefasEquipe').modal('show');
                    }
                }, 500);

                $('#btnEstouCienteTarefas').on('click', function() {
                    $('#modalAlertaTarefasEquipe').modal('hide');
                    $.ajax({
                        url: '/tarefas/marcar-alerta-lido',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        }
                    });
                });
            });
        </script>
    @endif

    <!-- 2. MODAL DE AVISO POPUP GERAL DO SISTEMA (ORIGINAL) -->
    <div class="modal fade" id="modalAvisoPopUp" tabindex="-1" role="dialog" aria-labelledby="modalAvisoTitulo" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white py-4" id="modalAvisoHeader">
                    <h5 class="modal-title font-weight-bolder text-white" id="modalAvisoTitulo">
                        <i class="la la-bell text-white mr-2"></i> <span id="avisoTituloTexto">COMUNICADO IMPORTANTE</span>
                    </h5>
                    <span class="badge badge-light font-weight-bold ml-3" id="avisoBadgePrioridade">NORMAL</span>
                </div>
                <div class="modal-body py-6" style="max-height: 65vh; overflow-y: auto;">
                    <div class="text-muted small mb-3">
                        Publicado em: <strong id="avisoDataTexto">--/--/----</strong>
                    </div>
                    <div id="avisoCorpoTexto" class="font-size-lg text-dark-75 line-height-lg">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <input type="hidden" id="avisoIdAtual" value="">
                    <button type="button" class="btn btn-primary font-weight-bold px-6" id="btnConfirmarLeituraAviso" data-dismiss="modal">
                        <i class="la la-check"></i> Estou ciente / Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            @if(\Illuminate\Support\Facades\Route::has('alertas.pendente'))
            fetch('/alertas/pendente')
                .then(response => {
                    if (!response.ok) throw new Error('Não foi possível consultar avisos.');
                    return response.json();
                })
                .then(data => {
                    if (data && data.pendente) {
                        document.getElementById('avisoIdAtual').value = data.id;
                        document.getElementById('avisoTituloTexto').innerText = data.titulo;
                        document.getElementById('avisoBadgePrioridade').innerText = data.prioridade;
                        document.getElementById('avisoDataTexto').innerText = data.data;
                        document.getElementById('avisoCorpoTexto').innerHTML = data.texto;

                        let modalHeader = document.getElementById('modalAvisoHeader');
                        if (data.prioridade === 'ALTA' || data.prioridade === 'URGENTE') {
                            modalHeader.className = 'modal-header bg-danger text-white py-4';
                        } else if (data.prioridade === 'MEDIA') {
                            modalHeader.className = 'modal-header bg-warning text-dark py-4';
                        } else {
                            modalHeader.className = 'modal-header bg-primary text-white py-4';
                        }

                        function exibirAvisoSemSobreporModais() {
                            const $abertos = $('.modal.show').not('#modalAvisoPopUp');
                            if ($abertos.length) {
                                $abertos.one('hidden.bs.modal', exibirAvisoSemSobreporModais);
                                return;
                            }
                            $('#modalAvisoPopUp').modal('show');
                        }
                        if ($('#modalAlertaTarefasEquipe').length) {
                            $('#modalAlertaTarefasEquipe').one('hidden.bs.modal', exibirAvisoSemSobreporModais);
                        } else {
                            exibirAvisoSemSobreporModais();
                        }
                    }
                })
                .catch(err => console.error("Erro ao verificar avisos:", err));
            @endif

            $('#btnConfirmarLeituraAviso').on('click', function () {
                let avisoId = $('#avisoIdAtual').val();
                let token = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

                $('#modalAvisoPopUp').modal('hide');

                if (avisoId) {
                    $.ajax({
                        url: '/alertas/marcar-lido/' + avisoId,
                        type: 'POST',
                        data: {
                            _token: token
                        },
                        error: function(err) {
                            console.error("Erro ao registrar ciência do aviso:", err);
                        }
                    });
                }
            });
        });
    </script>

@endsection

{{-- Compatibilidade com a seção renomeada nos menus do parceiro, sem perder o delivery. --}}
@section('modal-alerta-sistema')
    @yield('global_delivery_alert')
@endsection

<script>
    window.SERVER_OFFSET_MIN = {{ Carbon::now()->getOffset() / 60 }};

    function getServerDate() {
        const d = new Date();
        const serverOffset = window.SERVER_OFFSET_MIN;
        const localOffset   = -d.getTimezoneOffset();
        const diffMin      = serverOffset - localOffset;
        d.setMinutes(d.getMinutes() + diffMin);
        return d;
    }

    function formatDateTimeLocal(d) {
        const pad = n => String(n).padStart(2,'0');
        return  d.getFullYear()
            + '-' + pad(d.getMonth()+1)
            + '-' + pad(d.getDate())
            + 'T' + pad(d.getHours())
            + ':' + pad(d.getMinutes())
            + ':' + pad(d.getSeconds());
    }
</script>
