@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush
@section('content')

    <header class="fer-page__heading"><h1>Agenda de Coletas</h1></header>
<style>
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        .cal-header {
            background: #3699FF;
            color: #fff;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border-radius: 4px;
        }
        .cal-day {
            background: #f8f9fa;
            border: 1px solid #ebedf2;
            border-radius: 6px;
            min-height: 120px;
            padding: 8px;
            position: relative;
        }
        .cal-day-empty {
            background: #f1f3f6;
            border: 1px dashed #e4e6ef;
            min-height: 120px;
            border-radius: 6px;
        }
        .cal-day-num {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 6px;
            color: #464e5f;
        }
        .cal-event-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4px;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 7px;
            border-radius: 4px;
            color: #fff !important;
            margin-bottom: 4px;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
            transition: transform 0.1s ease-in-out;
        }
        .cal-event-btn:hover {
            transform: scale(1.02);
        }
        .event-agendado { background-color: #ffa800; }
        .event-iniciado { background-color: #3699FF; }
        .event-finalizado { background-color: #1BC5BD; }
        .event-cancelado { background-color: #F64E60; }

        .badge-atrasado {
            background-color: #dc3545;
            color: white;
            padding: 2px 4px;
            font-size: 9px;
            border-radius: 3px;
            animation: blinker 1.2s linear infinite;
        }
        @keyframes blinker {
            50% { opacity: 0.3; }
        }
    </style>

    <div class="fer-page fer-operational fer-board card card-custom gutter-b">
        <div class="card-body">

            <div class="row mb-5">
                <div class="col-xl-3 col-sm-6">
                    <div class="card card-custom bg-success card-stretch gutter-b text-white p-4">
                        <div class="font-weight-bold font-size-h6">Coletas Realizadas</div>
                        <div class="font-size-h2 font-weight-bolder">{{ $totalRealizadas }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card card-custom bg-danger card-stretch gutter-b text-white p-4">
                        <div class="font-weight-bold font-size-h6">Coletas Canceladas</div>
                        <div class="font-size-h2 font-weight-bolder">{{ $totalCanceladas }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card card-custom bg-warning card-stretch gutter-b text-white p-4">
                        <div class="font-weight-bold font-size-h6">Agendadas (A Realizar)</div>
                        <div class="font-size-h2 font-weight-bolder">{{ $totalAgendadas }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card card-custom bg-primary card-stretch gutter-b text-white p-4">
                        <div class="font-weight-bold font-size-h6">Em Percurso</div>
                        <div class="font-size-h2 font-weight-bolder">{{ $totalEmPercurso }}</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <div>
                    <a href="/movimentacaoVeiculo/new" class="btn btn-success font-weight-bold mr-2">
                        <i class="la la-plus"></i> Nova Coleta / Agendamento
                    </a>
                    <a href="/movimentacaoVeiculo/relatorioColetas" class="btn btn-light-primary font-weight-bold">
                        <i class="la la-print"></i> Relatório de Coletas
                    </a>
                </div>
                <div class="d-flex align-items-center mt-2 mt-md-0">
                    <span class="mr-3 font-weight-bold">Status:</span>
                    <span class="badge badge-warning mr-2">🟡 Agendado</span>
                    <span class="badge badge-primary mr-2">🔵 Em Percurso</span>
                    <span class="badge badge-success mr-2">🟢 Finalizado</span>
                    <span class="badge badge-danger">🔴 Cancelado</span>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded">
                <a href="/movimentacaoVeiculo/agenda?mes={{ $mesAnterior }}" class="btn btn-outline-primary btn-sm font-weight-bold">
                    <i class="la la-angle-left"></i> Mês Anterior
                </a>
                <h4 class="font-weight-bolder text-dark mb-0">
                    @php
                        $mesesPt = [
                            'January' => 'Janeiro', 'February' => 'Fevereiro', 'March' => 'Março',
                            'April' => 'Abril', 'May' => 'Maio', 'June' => 'Junho',
                            'July' => 'Julho', 'August' => 'Agosto', 'September' => 'Setembro',
                            'October' => 'Outubro', 'November' => 'Novembro', 'December' => 'Dezembro'
                        ];
                        $nomeMes = $mesesPt[$dt->format('F')] ?? $dt->format('F');
                    @endphp
                    {{ $nomeMes }} / {{ $dt->format('Y') }}
                </h4>
                <a href="/movimentacaoVeiculo/agenda?mes={{ $mesProximo }}" class="btn btn-outline-primary btn-sm font-weight-bold">
                    Próximo Mês <i class="la la-angle-right"></i>
                </a>
            </div>

            <div class="cal-grid mb-2">
                <div class="cal-header">Dom</div>
                <div class="cal-header">Seg</div>
                <div class="cal-header">Ter</div>
                <div class="cal-header">Qua</div>
                <div class="cal-header">Qui</div>
                <div class="cal-header">Sex</div>
                <div class="cal-header">Sáb</div>
            </div>

            <div class="cal-grid">
                @for ($i = 0; $i < $primeiroDiaSemana; $i++)
                    <div class="cal-day-empty"></div>
                @endfor

                @for ($dia = 1; $dia <= $diasNoMes; $dia++)
                    @php
                        $dataCurrent = $dt->copy()->day($dia)->format('Y-m-d');
                        $coletasDoDia = $coletasPorData[$dataCurrent] ?? [];
                    @endphp

                    <div class="cal-day">
                        <div class="cal-day-num">{{ sprintf('%02d', $dia) }}</div>

                        @foreach($coletasDoDia as $coleta)
                            @php
                                $classeStatus = match($coleta->status) {
                                    'agendado' => 'event-agendado',
                                    'iniciado' => 'event-iniciado',
                                    'finalizado', 'concluida' => 'event-finalizado',
                                    'cancelado' => 'event-cancelado',
                                    default => 'event-agendado'
                                };

                                $placa = $coleta->veiculo->placa ?? 'S/ Placa';

                                // =========================================================================
                                // HTML MULTI-STOP COM BOTÕES INDIVIDUAIS INJETADOS
                                // =========================================================================
                                $htmlMultiStop = '';
                                $isMultiStop = 'false';

                                if (isset($coleta->destinos) && $coleta->destinos->count() > 0) {
                                    $isMultiStop = 'true';
                                    foreach($coleta->destinos as $dest) {
                                        $htmlMultiStop .= '<div class="mb-4 border-bottom pb-3"><div class="d-flex align-items-center mb-1"><span class="badge badge-secondary mr-2">'.$dest->ordem.'º</span>';

                                        if ($dest->cliente) {
                                            $htmlMultiStop .= '<b class="text-dark">'.htmlspecialchars($dest->cliente->razao_social ?? $dest->cliente->nome).'</b>';
                                        } elseif ($dest->fornecedor) {
                                            $htmlMultiStop .= '<b class="text-dark">[FOR] '.htmlspecialchars($dest->fornecedor->razao_social ?? $dest->fornecedor->nome).'</b>';
                                        } else {
                                            $htmlMultiStop .= '<b class="text-dark">Endereço Avulso</b>';
                                        }
                                        $htmlMultiStop .= '</div>';

                                        if ($dest->endereco_completo) {
                                            $htmlMultiStop .= '<small class="text-muted d-block ml-8 mb-2">'.htmlspecialchars($dest->endereco_completo).'</small>';
                                        }

                                        // Lógica de tempo e botões de Check-in para esta parada
                                        $cheg = $dest->data_hora_chegada ? \Carbon\Carbon::parse($dest->data_hora_chegada)->format('d/m/Y H:i') : null;
                                        $sai = $dest->data_hora_saida ? \Carbon\Carbon::parse($dest->data_hora_saida)->format('d/m/Y H:i') : null;

                                        $htmlMultiStop .= '<div class="ml-8 mt-2 d-flex align-items-center" style="font-size: 0.9rem;">';
                                        if ($cheg && $sai) {
                                            $htmlMultiStop .= '<span class="text-success font-weight-bold"><i class="la la-check-circle text-success mr-1"></i> Visitado ('.$cheg.' às '.$sai.')</span>';
                                        } elseif ($cheg) {
                                            $htmlMultiStop .= '<span class="text-primary font-weight-bold mr-3"><i class="la la-map-marker text-primary mr-1"></i> Chegou: '.$cheg.'</span>';
                                            $htmlMultiStop .= '<button class="btn btn-xs btn-outline-info font-weight-bold py-1 px-2" onclick="marcarHorarioDestino(\'saida_destino\', '.$dest->id.')">Registrar Saída</button>';
                                        } else {
                                            $htmlMultiStop .= '<span class="text-muted font-weight-bold mr-3"><i class="la la-clock text-muted mr-1"></i> Pendente</span>';
                                            $htmlMultiStop .= '<button class="btn btn-xs btn-outline-success font-weight-bold py-1 px-2" onclick="marcarHorarioDestino(\'chegada_destino\', '.$dest->id.')">Registrar Chegada</button>';
                                        }
                                        $htmlMultiStop .= '</div></div>';
                                    }
                                } else {
                                    $htmlMultiStop = '<b>'.htmlspecialchars($coleta->cliente->razao_social ?? $coleta->destino ?? 'Não informado').'</b>';
                                }

                                $saidaPrevista = \Carbon\Carbon::parse($coleta->data_hora_saida)->format('d/m/Y H:i');
                                $saidaReal = $coleta->data_hora_saida_real ? \Carbon\Carbon::parse($coleta->data_hora_saida_real)->format('d/m/Y H:i') : '---';
                                $chegadaBase = $coleta->data_hora_chegada ? \Carbon\Carbon::parse($coleta->data_hora_chegada)->format('d/m/Y H:i') : '---';

                                $textoAtraso = $coleta->status_pontualidade_saida ?? '';
                                $textoAtraso = preg_replace_callback('/(\d+\.\d+)/', function($m) { return round($m[1]); }, $textoAtraso);

                                $isAtrasado = ($coleta->status == 'agendado' && \Carbon\Carbon::parse($coleta->data_hora_saida)->isPast());

                                $tempoTotal = '';
                                $tempoCliente = '';

                                if ($coleta->status == 'finalizado' && $coleta->data_hora_saida_real && $coleta->data_hora_chegada) {
                                    $tempoTotal = \Carbon\Carbon::parse($coleta->data_hora_saida_real)->diffForHumans(\Carbon\Carbon::parse($coleta->data_hora_chegada), true);
                                }

                                if ($coleta->data_hora_chegada_cliente && $coleta->data_hora_saida_cliente) {
                                    $tempoCliente = \Carbon\Carbon::parse($coleta->data_hora_chegada_cliente)->diffForHumans(\Carbon\Carbon::parse($coleta->data_hora_saida_cliente), true);
                                }

                                $htmlMultiStopB64 = base64_encode($htmlMultiStop);
                                $pontualidadeB64 = base64_encode($textoAtraso);
                            @endphp

                            <div class="cal-event-btn {{ $classeStatus }}"
                                 onclick="exibirResumo(
                                '{{ $coleta->id }}',
                                '{{ $placa }}',
                                '{{ $htmlMultiStopB64 }}',
                                '{{ addslashes($coleta->motorista->nome ?? 'Não informado') }}',
                                '{{ $coleta->motorista_id }}',
                                '{{ ucfirst($coleta->status) }}',
                                '{{ $coleta->data_chegada_cliente_formatada ?? '---' }}',
                                '{{ $coleta->data_saida_cliente_formatada ?? '---' }}',
                                '{{ $saidaPrevista }}',
                                '{{ $saidaReal }}',
                                '{{ $pontualidadeB64 }}',
                                '{{ $chegadaBase }}',
                                '{{ $tempoTotal }}',
                                '{{ $tempoCliente }}',
                                '{{ $isMultiStop }}'
                             )">
                                <div>
                                    <i class="la la-truck"></i>
                                    <span>{{ $placa }}</span>
                                </div>
                                @if($isAtrasado)
                                    <span class="badge-atrasado">Atrasado</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endfor
            </div>

        </div>
    </div>

    <!-- MODAL DE RESUMO COM AÇÕES RÁPIDAS -->
    <div class="modal fade" id="modalResumoColeta" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="fer-dialog modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold text-primary">
                        <i class="la la-truck text-primary mr-1"></i> Detalhes da Viagem
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label class="text-muted mb-0 font-weight-bold">Veículo / Placa:</label>
                        <p class="font-weight-bolder text-dark font-size-h6 mb-0" id="mPlaca">-</p>
                    </div>

                    <div class="form-group mb-2">
                        <label class="text-muted mb-0 font-weight-bold">Destinos / Rota:</label>
                        <div class="p-3 bg-light rounded mt-1 shadow-sm" id="mCliente">-</div>
                    </div>

                    <div class="form-group mb-2 mt-3">
                        <label class="text-muted mb-0 font-weight-bold">Motorista:</label>
                        <p class="font-weight-bold text-dark mb-0" id="mMotorista">-</p>
                    </div>
                    <div class="form-group mb-2">
                        <label class="text-muted mb-0 font-weight-bold">Status Atual:</label>
                        <p class="font-weight-bold mb-0" id="mStatus">-</p>
                    </div>

                    <hr>

                    <!-- 1. SAÍDA DA GARAGEM -->
                    <div class="row align-items-center mb-3 bg-light-warning p-2 rounded">
                        <div class="col-7">
                            <label class="text-muted mb-0 font-weight-bold">Previsto para Saída:</label>
                            <p class="font-weight-bold text-dark mb-0" id="mSaidaPrevista">-</p>

                            <label class="text-muted mb-0 font-weight-bold mt-1">Saída Real Garagem:</label>
                            <p class="font-weight-bold text-dark mb-0" id="mSaidaReal">-</p>
                        </div>
                        <div class="col-5 text-right">
                            <button type="button" class="btn btn-warning btn-sm font-weight-bold text-white mb-1 btn-acao-horario" onclick="marcarHorario('saida_garagem')">
                                <i class="la la-truck"></i> Saiu Garagem
                            </button>
                            <div id="mBadgePontualidade" class="mt-1"></div>
                        </div>
                    </div>

                    <!-- 2. CHEGADA NO CLIENTE (SÓ MOSTRA SE FOR VIAGEM ANTIGA/SIMPLES) -->
                    <div class="row align-items-center mb-3 bg-light p-2 rounded" id="blocoLegadoChegada">
                        <div class="col-7">
                            <label class="text-muted mb-0 font-weight-bold">Chegada Cliente:</label>
                            <p class="font-weight-bold text-dark mb-0" id="mChegada">-</p>
                        </div>
                        <div class="col-5 text-right">
                            <button type="button" class="btn btn-outline-success btn-sm font-weight-bold btn-acao-horario" onclick="marcarHorario('chegada')">
                                <i class="la la-clock"></i> Chegou Agora
                            </button>
                        </div>
                    </div>

                    <!-- 3. SAÍDA DO CLIENTE (SÓ MOSTRA SE FOR VIAGEM ANTIGA/SIMPLES) -->
                    <div class="row align-items-center mb-3 bg-light p-2 rounded" id="blocoLegadoSaida">
                        <div class="col-7">
                            <label class="text-muted mb-0 font-weight-bold">Saída Cliente:</label>
                            <p class="font-weight-bold text-dark mb-0" id="mSaida">-</p>
                        </div>
                        <div class="col-5 text-right">
                            <button type="button" class="btn btn-outline-info btn-sm font-weight-bold btn-acao-horario" onclick="marcarHorario('saida')">
                                <i class="la la-clock"></i> Saiu Agora
                            </button>
                        </div>
                    </div>

                    <!-- 4. RETORNO À GARAGEM -->
                    <div class="row align-items-center mb-3 bg-light-success p-2 rounded">
                        <div class="col-7">
                            <label class="text-muted mb-0 font-weight-bold">Retorno à Base (Fim):</label>
                            <p class="font-weight-bold text-dark mb-0" id="mChegadaBase">-</p>
                        </div>
                        <div class="col-5 text-right">
                            <button type="button" class="btn btn-success btn-sm font-weight-bold text-white mb-1 btn-acao-horario" onclick="marcarHorario('chegada_garagem')">
                                <i class="la la-check-circle"></i> Chegou Base
                            </button>
                        </div>
                    </div>

                    <div class="row mt-3" id="mResumoViagemContainer" style="display: none;">
                        <div class="col-12">
                            <div class="alert alert-custom alert-light-primary fade show mb-0 p-3" role="alert">
                                <div class="alert-icon"><i class="flaticon-information text-primary"></i></div>
                                <div class="alert-text font-weight-bold" id="mResumoViagemTexto"></div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="btnModalCancelar" onclick="abrirCancelamento()">
                        <i class="la la-times-circle"></i> Cancelar Coleta
                    </button>
                    <div>
                        <button type="button" class="btn btn-info mr-2" id="btnModalAbastecimento" onclick="pedirAbastecimento()">
                            <i class="la la-gas-pump"></i> Link Abastecimento
                        </button>
                        <a id="btnModalEditar" href="#" class="btn btn-warning">
                            <i class="la la-edit"></i> Editar
                        </a>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_cancelar" tabindex="-1" role="dialog">
        <div class="fer-dialog modal-dialog" role="document">
            <form id="form_cancelar" method="post" action="">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">Motivo do Cancelamento</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <label class="font-weight-bold">Informe o motivo do cancelamento:</label>
                        <textarea name="motivo_cancelamento" class="form-control" rows="3" required placeholder="Digite o motivo..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('javascript')
    <script>
        var idColetaAtual = null;

        function exibirResumo(id, placa, clienteHtmlB64, motorista, motoristaId, status, chegada, saida, prevista, real, pontualidadeB64, chegadaBase, tempoTotal, tempoCliente, isMultiStop) {
            idColetaAtual = id;

            $('#mPlaca').text(placa);

            let htmlClienteDecodificado = clienteHtmlB64 ? decodeURIComponent(escape(window.atob(clienteHtmlB64))) : 'Nenhum cliente informado';
            $('#mCliente').html(htmlClienteDecodificado);

            $('#mMotorista').text(motorista);
            $('#mStatus').text(status);
            $('#mChegada').text(chegada);
            $('#mSaida').text(saida);

            $('#mSaidaPrevista').text(prevista);
            $('#mSaidaReal').text(real);

            let htmlPontualidadeDecodificada = pontualidadeB64 ? decodeURIComponent(escape(window.atob(pontualidadeB64))) : '---';
            $('#mBadgePontualidade').html(htmlPontualidadeDecodificada);

            $('#mChegadaBase').text(chegadaBase || '---');

            $('#btnModalEditar').attr('href', '/movimentacaoVeiculo/edit/' + id);

            if (status === 'Cancelado' || status === 'Finalizado') {
                $('#btnModalCancelar').hide();
            } else {
                $('#btnModalCancelar').show();
            }

            // ESCONDE OS BOTÕES ANTIGOS DE CLIENTE SE FOR MULTI-STOP
            if (isMultiStop === 'true') {
                $('#blocoLegadoChegada').hide();
                $('#blocoLegadoSaida').hide();
            } else {
                $('#blocoLegadoChegada').show();
                $('#blocoLegadoSaida').show();
            }

            if (status === 'Finalizado' && tempoTotal) {
                let textoResumo = `Tempo Total da Viagem: ${tempoTotal}`;
                $('#mResumoViagemTexto').html(textoResumo);
                $('#mResumoViagemContainer').show();
            } else {
                $('#mResumoViagemContainer').hide();
            }

            $('#modalResumoColeta').modal('show');
        }

        // Função Original para os Botões Genéricos e Retorno
        function marcarHorario(tipo) {
            if (!idColetaAtual) return;

            $.post('/movimentacaoVeiculo/registrarHorarioCliente/' + idColetaAtual, {
                _token: '{{ csrf_token() }}',
                tipo: tipo
            }, function(res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Erro: ' + res.mensagem);
                }
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.mensagem : 'Erro ao processar a solicitação.';
                alert('Aviso: ' + msg);
            });
        }

        // NOVA Função para Marcar Parada a Parada na Lista de Destinos
        function marcarHorarioDestino(tipo, destinoId) {
            if (!idColetaAtual) return;

            $.post('/movimentacaoVeiculo/registrarHorarioCliente/' + idColetaAtual, {
                _token: '{{ csrf_token() }}',
                tipo: tipo,
                destino_id: destinoId
            }, function(res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Erro: ' + res.mensagem);
                }
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.mensagem : 'Erro ao processar a solicitação.';
                alert('Aviso: ' + msg);
            });
        }

        function abrirCancelamento() {
            if (!idColetaAtual) return;
            $('#modalResumoColeta').modal('hide');
            $('#form_cancelar').attr('action', '/movimentacaoVeiculo/cancelar/' + idColetaAtual);
            $('#modal_cancelar').modal('show');
        }

        function pedirAbastecimento() {
            if (!idColetaAtual) return;
            if (confirm('Deseja enviar o link de abastecimento para o WhatsApp do motorista?')) {
                window.location.href = '/movimentacaoVeiculo/enviarLinkAbastecimento/' + idColetaAtual;
            }
        }
    </script>
@endsection
