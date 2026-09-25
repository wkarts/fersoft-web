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

{{-- 1. DEFINE O LAYOUT PAI NO TOPO --}}
@extends('default/menu_'.$tipoMenu)

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
<div class="modal fade" id="modalNovoPedidoAlerta" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 99999;">
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

    function alterarStatusPedido(estado, motivo) {
        if (!pedidoPendenteAtualId) return;

        $.ajax({
            url: '/pedidosDelivery/actualizarStatusKanban',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: pedidoPendenteAtualId,
                estado: estado,
                motivo: motivo || ''
            },
            success: function(response) {
                if (!response || response.sucesso !== true) {
                    exibirErroPedido(response && response.mensagem
                        ? response.mensagem
                        : 'Não foi possível atualizar o pedido.');
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
                    }, 800);
                } else {
                    pedidoPendenteAtualId = null;
                }
            },
            error: function(xhr) {
                let mensagem = 'Erro ao atualizar o pedido.';
                if (xhr.responseJSON && xhr.responseJSON.mensagem) {
                    mensagem = xhr.responseJSON.mensagem;
                }
                exibirErroPedido(mensagem);
            }
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
                            <p><strong>Pagamento:</strong> ${response.forma_pagamento || '--'}</p>
                            <p><strong>Tipo de Entrega:</strong> ${response.tipo_entrega || '--'}</p>
                        </div>
                    </div>
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