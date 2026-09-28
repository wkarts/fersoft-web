@extends('default.layout')

@section('css')
    <style type="text/css">
        body.loading .modal-loading {
            display: block;
        }

        .modal-loading {
            display: none;
            position: fixed;
            z-index: 10000;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: rgba(255, 255, 255, 0.8) url("/loading.gif") 50% 50% no-repeat;
        }
    </style>
@endsection

@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-body">
            <form method="get" class="row">
                <div class="col-md-2">
                    <label>Data inicial</label>
                    <input type="date" value="{{ $start_date }}" class="form-control" name="start_date">
                </div>
                <div class="col-md-2">
                    <label>Data final</label>
                    <input type="date" value="{{ $end_date }}" class="form-control" name="end_date">
                </div>
                <div class="col-md-2">
                    <br>
                    <button class="btn btn-info mt-2">
                        Filtrar
                    </button>
                </div>

                <!-- NOVO BOTÃO DE ENVIO EM LOTE -->
                <div class="col-md-4">
                    <br>
                    <button type="button" class="btn btn-success mt-2" onclick="emitirLoteContingencia()">
                        <i class="las la-rocket"></i> Enviar Lote Automático
                    </button>
                </div>
            </form>

            <input type="hidden" id="_token" value="{{ csrf_token() }}">

            <div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <!-- CHECKBOX SELECIONAR TODOS -->
                            <th style="width: 40px;"><input type="checkbox" id="selecionar-tudo-lote"></th>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Vendedor</th>
                            <th>Valor total</th>
                            <th>Desconto</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($vendas as $v)
                            <tr>
                                <!-- CHECKBOX INDIVIDUAL DA LINHA -->
                                <td>
                                    @if($v->reenvio_contigencia == 0 && $v->contigencia == 1)
                                        <input type="checkbox" class="checkbox-venda-lote" value="{{ $v->id }}">
                                    @endif
                                </td>
                                <td>{{ $v->numero_sequencial }}</td>
                                <td>{{ $v->cliente->razao_social ?? 'NAO IDENTIFCADO' }}</td>
                                <td>{{ $v->estado }}</td>
                                <td>{{ $v->vendedor_setado ? $v->vendedor_setado->nome : '--' }}</td>
                                <td>{{ moeda($v->valor_total) }}</td>
                                <td>{{ moeda($v->desconto) }}</td>
                                <td>{{ __date($v->created_at) }}</td>
                                <td>
                                    @if($v->reenvio_contigencia == 0 && $v->contigencia == 1)
                                        <!-- A Lógica JS Complexa foi removida do onclick para não quebrar o Laravel Blade -->
                                        <a title="RETRANSMITIR EM CONTIGÊNCIA" id="btn_envia_{{ $v->id }}" class="btn btn-warning spinner-white spinner-right btn-sm" onclick="confirmarTransmissao({{ $v->id }})" href="#!">
                                            <i class="las la-paper-plane"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-loading loading-class"></div>
@endsection

@section('javascript')
    <script type="text/javascript" src="/js/frenteCaixa.js"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script>
        window.onload = function() {
            $(document).on('change', '#selecionar-tudo-lote', function() {
                $('.checkbox-venda-lote').prop('checked', $(this).prop('checked'));
            });
        };

        // Exibe o popup de confirmação sem quebrar o Blade
        function confirmarTransmissao(vendaId) {
            swal({
                title: "Atenção!",
                text: "Deseja enviar esta venda em contingência para a Sefaz?",
                icon: "warning",
                buttons: ["Cancelar", "Sim, enviar"],
                dangerMode: false,
            }).then((sim) => {
                if (sim) {
                    transmitirContigencia(vendaId);
                }
            });
        }

        function transmitirContigencia(vendaId) {
            $('.modal-loading').show();
            $.ajax({
                url: '/nfce/gerar',
                type: 'POST',
                data: { vendaId: vendaId, _token: $('#_token').val() },
                success: function(response) {
                    $('.modal-loading').hide();

                    let resStr = typeof response === 'object' ? JSON.stringify(response) : response;

                    if (resStr.toLowerCase().includes('rejeicao') || resStr.toLowerCase().includes('rejeição') || resStr.toLowerCase().includes('erro')) {
                        swal("Rejeição da Sefaz!", "A SEFAZ recusou a nota ou ocorreu um erro de configuração. Verifique os dados fiscais.", "error").then(() => {
                            location.reload();
                        });
                    } else {
                        swal("Sucesso!", "NFC-e transmitida e autorizada com sucesso!", "success").then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(err) {
                    $('.modal-loading').hide();
                    swal("Erro Crítico", "Falha de comunicação com o servidor web. Verifique o log do sistema.", "error").then(() => {
                        location.reload();
                    });
                }
            });
        }

        function emitirLoteContingencia() {
            let idsSelecionados = [];
            $('.checkbox-venda-lote:checked').each(function() {
                idsSelecionados.push($(this).val());
            });

            if (idsSelecionados.length === 0) {
                swal("Atenção", "Selecione ao menos uma venda pendente marcando as caixinhas na tabela.", "warning");
                return;
            }

            swal({
                title: "Transmissão Automática",
                text: "Deseja transmitir as " + idsSelecionados.length + " nota(s) de contingência selecionada(s)?",
                icon: "info",
                buttons: ["Cancelar", "Sim, transmitir"],
                dangerMode: false,
            }).then((enviar) => {
                if (enviar) {
                    $('.modal-loading').show();
                    processarLoteFila(idsSelecionados, 0);
                }
            });
        }

        function processarLoteFila(listaIds, indexAtual) {
            if (indexAtual >= listaIds.length) {
                $('.modal-loading').hide();
                swal("Lote Concluído!", "A fila de transmissão terminou. A página vai ser atualizada para ver os resultados.", "success").then(() => {
                    location.reload();
                });
                return;
            }

            let vendaIdAtual = listaIds[indexAtual];

            $.ajax({
                url: '/nfce/gerar',
                type: 'POST',
                data: { vendaId: vendaIdAtual, _token: $('#_token').val() },
                success: function(response) {
                    processarLoteFila(listaIds, indexAtual + 1);
                },
                error: function(err) {
                    console.error('Erro na nota: ' + vendaIdAtual, err);
                    processarLoteFila(listaIds, indexAtual + 1);
                }
            });
        }
    </script>
@endsection
